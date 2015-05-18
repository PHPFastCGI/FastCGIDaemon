<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\Exception\DaemonException;
use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Http\RequestEnvironmentBuilder;
use PHPFastCGI\FastCGIDaemon\Http\ResponseInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class ConnectionHandler
{
    const READ_LENGTH = 4096;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var ConnectionInterface 
     */
    protected $connection;

    /**
     * @var array
     */
    protected $requests;

    /**
     * @var string
     */
    protected $buffer;

    /**
     * @var int
     */
    protected $bufferLength;

    /**
     * Constructor.
     * 
     * @param KernelInterface     $kernel
     * @param ConnectionInterface $connection
     */
    public function __construct(KernelInterface $kernel, ConnectionInterface $connection)
    {
        $this->kernel       = $kernel;
        $this->connection   = $connection;
        $this->requests     = [];
        $this->buffer       = '';
        $this->bufferLength = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        try {
            $data = $this->connection->read(self::READ_LENGTH);
            $dataLength = strlen($data);

            // Connection has been closed
            if (0 === $dataLength) {
                $this->connection->close();
                return;
            }

            $this->buffer       .= $data;
            $this->bufferLength += $dataLength;

            while (null !== ($record = $this->readRecord())) {
                $this->processRecord($record);
            }
        } catch (DaemonException $exception) {
            $this->close();
            // TODO: Logging
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->buffer       = null;
        $this->bufferLength = 0;

        foreach ($this->requests as $request) {
            $request['builder']->clean();
        }

        $this->requests = [];

        $this->connection->close();
    }

    protected function readRecord()
    {
        // Not enough data to read header
        if ($this->bufferLength < 8) {
            return null;
        }

        $headerData = substr($this->buffer, 0, 8);

        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

        $record = unpack($headerFormat, $headerData);

        // Not enough data to read rest of record
        if ($this->bufferLength - 8 < $record['contentLength'] + $record['paddingLength']) {
            return null;
        }

        $record['contentData'] = substr($this->buffer, 8, $record['contentLength']);

        // Remove the record from the buffer
        $recordSize = 8 + $record['contentLength'] + $record['paddingLength'];

        $this->buffer        = substr($this->buffer, $recordSize);
        $this->bufferLength -= $recordSize;

        return $record;
    }

    protected function writeRecord($requestId, $type, $content = null)
    {
        $contentLength = null === $content ? 0 : strlen($content);

        $headerData = pack('CCnnxx', DaemonInterface::FCGI_VERSION_1, $type, $requestId, $contentLength);

        $this->connection->write($headerData, 8);

        if (null !== $content) {
            $this->connection->write($content, $contentLength);
        }
    }

    protected function writeStream($requestId, $stream)
    {
        while (!feof($stream)) {
            $data = fread($stream, 65535);

            if (false !== $data) {
                $this->writeRecord($requestId, DaemonInterface::FCGI_STDOUT, $data);
            }
        }
    }

    protected function endRequest($requestId, $appStatus = 0, $protocolStatus = DaemonInterface::FCGI_REQUEST_COMPLETE)
    {
        $content = pack('NCx3', $appStatus, $protocolStatus);
        $this->writeRecord($requestId, DaemonInterface::FCGI_END_REQUEST, $content);

        $keepAlive = $this->requests[$requestId]['keepAlive'];

        $this->requests[$requestId]['builder']->clean();
        unset($this->requests[$requestId]);

        if (!$keepAlive) {
            $this->close();
        }
    }

    protected function processRecord(array $record)
    {
        $requestId = $record['requestId'];

        $content = 0 === $record['contentLength'] ? null : $record['contentData'];

        switch ($record['type']) {
            case DaemonInterface::FCGI_BEGIN_REQUEST:
                $this->processBeginRequestRecord($requestId, $content);
                break;

            case DaemonInterface::FCGI_PARAMS:
                $this->processParamsRecord($requestId, $content);
                break;

            case DaemonInterface::FCGI_STDIN:
                $this->processStdinRecord($requestId, $content);
                break;

            case DaemonInterface::FCGI_ABORT_REQUEST:
                $this->processAbortRequestRecord($requestId);
                break;

            default:
                throw new ProtocolException('Unexpected packet of unkown type: ' . $record['type']);
        }
    }

    protected function processBeginRequestRecord($requestId, $contentData)
    {
        if (isset($this->requests[$requestId])) {
            throw new ProtocolException('Unexpected FCGI_BEGIN_REQUEST record');
        }

        $contentFormat = 'nrole/Cflags/x5';

        $content = unpack($contentFormat, $contentData);

        $keepAlive = DaemonInterface::FCGI_KEEP_CONNECTION & $content['flags'];

        if (DaemonInterface::FCGI_RESPONDER !== $content['role']) {
            $this->writeEndRequestRecord($requestId, 0, DaemonInterface::FCGI_UNKNOWN_ROLE);

            if (!$keepAlive) {
                $this->connection->close();
            }
        } else {
            $this->requests[$requestId] = [
                'keepAlive' => $keepAlive,
                'builder'   => new RequestEnvironmentBuilder()
            ];
        }
    }

    protected function processParamsRecord($requestId, $contentData)
    {
        if (!isset($this->requests[$requestId])) {
            throw new ProtocolException('Unexpected FCGI_PARAMS record');
        }

        if (null === $contentData) {
            return;
        }

        $initialBytes = unpack('C5', $contentData);

        $extendedLengthName  = $initialBytes[1] & 0x80;
        $extendedLengthValue = $extendedLengthName ? $initialBytes[5] & 0x80 : $initialBytes[2] & 0x80;

        $structureFormat = (
            ($extendedLengthName  ? 'N' : 'C') . 'nameLength/' .
            ($extendedLengthValue ? 'N' : 'C') . 'valueLength'
        );

        $structure = unpack($structureFormat, $contentData);

        if ($extendedLengthName) {
            $structure['nameLength'] &= 0x7FFFFFFF;
        }

        if ($extendedLengthValue) {
            $structure['valueLength'] &= 0x7FFFFFFF;
        }

        $skipLength = ($extendedLengthName ? 4 : 1) + ($extendedLengthValue ? 4 : 1);

        $contentFormat = (
            'x' . $skipLength               . '/'     .
            'a' . $structure['nameLength']  . 'name/' .
            'a' . $structure['valueLength'] . 'value/'
        );

        $content = unpack($contentFormat, $contentData);

        $this->requests[$requestId]['builder']->addParam($content['name'], $content['value']);
    }

    protected function processStdinRecord($requestId, $contentData)
    {
        if (!isset($this->requests[$requestId])) {
            throw new ProtocolException('Unexpected FCGI_STDIN record');
        }

        if (null === $contentData) {
            $this->dispatchRequest($requestId);
            return;
        }

        $this->requests[$requestId]['builder']->addStdin($contentData);
    }

    protected function processAbortRequestRecord($requestId)
    {
        if (!isset($this->requests[$requestId])) {
            throw new ProtocolException('Unexpected FCG_ABORT_REQUEST record');
        }

        $this->endRequest($requestId);
    }

    protected function dispatchRequest($requestId)
    {
        $builder = $this->requests[$requestId]['builder'];

        $requestEnvironment = $builder->getRequestEnvironment();

        $response = $this->kernel->handleRequest($requestEnvironment);

        $builder->clean();

        $body = $response->getBody();

        try {
            $this->sendResponse($requestId, $response);
        } catch (DaemonException $exception) {
            if (is_resource($body)) {
                fclose($body);
            }

            throw $exception;
        }

        if (is_resource($body)) {
            fclose($body);
        }
    }

    protected function sendResponse($requestId, ResponseInterface $response)
    {
        $outputData = "Status: {$response->getStatusCode()} {$response->getReasonPhrase()}\r\n";

        foreach ($response->getHeaderLines() as $headerLine) {
            $outputData .= $headerLine . "\r\n";
        }

        $outputData .= "\r\n";

        $body = $response->getBody();

        if (is_string($body)) {
            $outputData .= $body;
        }

        $outputChunks = str_split($outputData, 65535);

        foreach ($outputChunks as $chunk) {
            $this->writeRecord($requestId, DaemonInterface::FCGI_STDOUT, $chunk);
        }

        if (is_resource($body)) {
            $this->writeStream($requestId, $body);
        }

        $this->writeRecord($requestId, DaemonInterface::FCGI_STDOUT);
        $this->endRequest($requestId);
    }
}
