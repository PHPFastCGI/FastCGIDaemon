<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Exception\AbstractRequestException;
use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;
use PHPFastCGI\FastCGIDaemon\Exception\MultiplexingUnsupportedException;
use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Exception\UnknownRoleException;
use PHPFastCGI\FastCGIDaemon\Http\Request;

class SingleplexedResponderConnectionHandler implements
    SingleplexedConnectionHandlerInterface
{
    const STATE_READY_FOR_REQUEST  = 0;
    const STATE_READY_FOR_RESPONSE = 1;
    const STATE_DEAD               = 2;

    protected $connection;
    protected $state              = self::STATE_READY_FOR_REQUEST;
    protected $requestId          = null;
    protected $stdin              = '';
    protected $params             = array();
    protected $gotLastStdinRecord = false;
    protected $gotLastParamRecord = false;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    protected function ready()
    {
        return $this->gotLastStdinRecord && $this->gotLastParamRecord;
    }

    protected function createRequest()
    {
        $this->state = self::STATE_READY_FOR_RESPONSE;

        return new Request($this, $this->requestId, $this->params, $this->stdin);
    }

    protected function readRecord()
    {
        $headerData = $this->connection->read(8);

        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/' .
            'CpaddingLength/x';

        $record = unpack($headerFormat, $headerData);

        $record['contentData'] = $this->connection->read($record['contentLength']);
        $this->connection->read($record['paddingLength']);

        return $record;
    }

    protected function readBeginRequestRecord()
    {
        $record = $this->readRecord();

        if (DaemonInterface::FCGI_BEGIN_REQUEST !== $record['type']) {
            throw new ProtocolException('Expected begin request record');
        }

        $this->requestId = $record['requestId'];

        $contentFormat = 'nrole/Cflags/x5';

        $content = unpack($contentFormat, $record['contentData']);

        if (DaemonInterface::FCGI_KEEP_CONNECTION & $content['flags']) {
            throw new MultiplexingUnsupportedException;
        }

        if (DaemonInterface::FCGI_RESPONDER !== $content['role']) {
            throw new UnknownRoleException;
        }
    }

    protected function readNextRecord()
    {
        $record = $this->readRecord();

        if ($this->requestId !== $record['requestId']) {
            throw new ProtocolException('Received invalid request id');
        }

        switch ($record['type']) {
            case DaemonInterface::FCGI_PARAMS:
                if (0 === $record['contentLength']) {
                    $this->gotLastParamRecord = true;
                } else {
                    $initialBytes = unpack('C5', $record['contentData']);

                    $extendedLengthName  = $initialBytes[1] & 0x80;
                    $extendedLengthValue = $extendedLengthName ?
                        $initialBytes[5] & 0x80 : $initialBytes[2] & 0x80;

                    $structureFormat = (
                        ($extendedLengthName  ? 'N' : 'C') . 'nameLength/' .
                        ($extendedLengthValue ? 'N' : 'C') . 'valueLength'
                    );

                    $structure = unpack($structureFormat, $record['contentData']);

                    if ($extendedLengthName) {
                        $structure['nameLength'] &= 0x7FFFFFFF;
                    }

                    if ($extendedLengthValue) {
                        $structure['valueLength'] &= 0x7FFFFFFF;
                    }

                    $skipLength = ($extendedLengthName ? 4 : 1) +
                        ($extendedLengthValue ? 4 : 1);

                    $contentFormat = (
                        'x' . $skipLength               . '/'     .
                        'a' . $structure['nameLength']  . 'name/' .
                        'a' . $structure['valueLength'] . 'value/'
                    );

                    $content = unpack($contentFormat, $record['contentData']);

                    $this->params[$content['name']] = $content['value'];
                }

                break;

            case DaemonInterface::FCGI_STDIN:
                if (0 === $record['contentLength']) {
                    $this->gotLastStdinRecord = true;
                } else {
                    $this->stdin .= $record['contentData'];
                }

                break;

            default:
                throw new ProtocolException(
                    'Unrecognised record for responder role');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        if ($this->state !== self::STATE_READY_FOR_REQUEST) {
            throw new \LogicException('Connection handler not ready');
        }

        try {
            $this->readBeginRequestRecord();

            do {
                $this->readNextRecord();
            } while (!$this->ready());

            return $this->createRequest();
        } catch (ProtocolException $protocolException) {
            if ($protocolException instanceof AbstractRequestException) {
                $protocolStatus = $protocolException->getProtocolStatus();
                $this->writeEndRequestRecord(0, $protocolStatus);
            }

            echo $protocolException->getMessage() . PHP_EOL;
            $this->connection->close();
        } catch (ConnectionException $connectionException) { }

        return null;
    }

    protected function writeRecord($type, $content, $contentLength)
    {
        $headerData = pack('CCnnxx', DaemonInterface::FCGI_VERSION_1, $type,
            $this->requestId, $contentLength);

        $this->connection->write($headerData, 8);
        $this->connection->write($content, $contentLength);
    }

    protected function writeEndRequestRecord($appStatus, $protocolStatus =
        DaemonInterface::FCGI_REQUEST_COMPLETE)
    {
        $content = pack('NCx3', $appStatus, $protocolStatus);
        $this->writeRecord(DaemonInterface::FCGI_END_REQUEST, $content, 8);
    }

    /**
     * {@inheritdoc}
     */
    public function sendResponse(Request $request, $response)
    {
        if ($this->state !== self::STATE_READY_FOR_RESPONSE) {
            throw new \LogicException('Connection handler not ready');
        } elseif ($this->requestId !== $request->getRequestId()) {
            throw new \LogicException('Invalid connection handler for request');
        }

        $chunks = str_split($response, 65535);

        try {
            foreach ($chunks as $chunk) {
                $this->writeRecord(DaemonInterface::FCGI_STDOUT, $chunk,
                    strlen($chunk));
            }

            $this->writeRecord(DaemonInterface::FCGI_STDOUT, null, 0);
            $this->writeEndRequestRecord(0);

            $this->connection->close();

            $this->state = self::STATE_DEAD;
        } catch (ConnectionException $exception) { }
    }
}
