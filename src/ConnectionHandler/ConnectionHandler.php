<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\Exception\DaemonException;
use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Http\Request;
use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zend\Diactoros\Stream;

/**
 * The default implementation of the ConnectionHandlerInterface.
 */
class ConnectionHandler implements ConnectionHandlerInterface
{
    const READ_LENGTH = 4096;

    /**
     * @var bool
     */
    private $shutdown;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var array
     */
    private $requests;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var int
     */
    private $bufferLength;

    /**
     * Constructor.
     *
     * @param KernelInterface     $kernel     The kernel to use to handle requests
     * @param ConnectionInterface $connection The connection to handle
     */
    public function __construct(KernelInterface $kernel, ConnectionInterface $connection)
    {
        $this->shutdown     = false;
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
        $data = $this->connection->read(self::READ_LENGTH);
        $dataLength = strlen($data);

        $this->buffer       .= $data;
        $this->bufferLength += $dataLength;

        while (null !== ($record = $this->readRecord())) {
            $this->processRecord($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        $this->shutdown = true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->buffer       = null;
        $this->bufferLength = 0;

        foreach ($this->requests as $request) {
            fclose($request['stdin']);
        }

        $this->requests = [];

        $this->connection->close();
    }

    /**
     * Read a record from the connection.
     *
     * @return array|null The record that has been read
     */
    private function readRecord()
    {
        // Not enough data to read header
        if ($this->bufferLength < 8) {
            return;
        }

        $headerData = substr($this->buffer, 0, 8);

        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

        $record = unpack($headerFormat, $headerData);

        // Not enough data to read rest of record
        if ($this->bufferLength - 8 < $record['contentLength'] + $record['paddingLength']) {
            return;
        }

        $record['contentData'] = substr($this->buffer, 8, $record['contentLength']);

        // Remove the record from the buffer
        $recordSize = 8 + $record['contentLength'] + $record['paddingLength'];

        $this->buffer        = substr($this->buffer, $recordSize);
        $this->bufferLength -= $recordSize;

        return $record;
    }

    /**
     * Process a record.
     *
     * @param array $record The record that has been read
     *
     * @throws ProtocolException If the client sends an unexpected record.
     */
    private function processRecord(array $record)
    {
        $requestId = $record['requestId'];

        $content = 0 === $record['contentLength'] ? null : $record['contentData'];

        if (DaemonInterface::FCGI_BEGIN_REQUEST === $record['type']) {
            $this->processBeginRequestRecord($requestId, $content);
        } elseif (!isset($this->requests[$requestId])) {
            throw new ProtocolException('Invalid request id for record of type: ' . $record['type']);
        } elseif (DaemonInterface::FCGI_PARAMS === $record['type']) {
            while (strlen($content) > 0) {
                $this->readNameValuePair($requestId, $content);
            }
        } elseif (DaemonInterface::FCGI_STDIN === $record['type']) {
            if (null !== $content) {
                fwrite($this->requests[$requestId]['stdin'], $content);
            } else {
                $this->dispatchRequest($requestId);
            }
        } elseif (DaemonInterface::FCGI_ABORT_REQUEST === $record['type']) {
            $this->endRequest($requestId);
        } else {
            throw new ProtocolException('Unexpected packet of type: '.$record['type']);
        }
    }

    /**
     * Process a FCGI_BEGIN_REQUEST record.
     *
     * @param int    $requestId   The request id sending the record
     * @param string $contentData The content of the record
     *
     * @throws ProtocolException If the FCGI_BEGIN_REQUEST record is unexpected
     */
    private function processBeginRequestRecord($requestId, $contentData)
    {
        if (isset($this->requests[$requestId])) {
            throw new ProtocolException('Unexpected FCGI_BEGIN_REQUEST record');
        }

        $contentFormat = 'nrole/Cflags/x5';

        $content = unpack($contentFormat, $contentData);

        $keepAlive = DaemonInterface::FCGI_KEEP_CONNECTION & $content['flags'];

        $this->requests[$requestId] = [
            'keepAlive' => $keepAlive,
            'stdin'     => fopen('php://temp', 'r+'),
            'params'    => [],
        ];

        if ($this->shutdown) {
            $this->endRequest($requestId, 0, DaemonInterface::FCGI_OVERLOADED);
            return;
        }

        if (DaemonInterface::FCGI_RESPONDER !== $content['role']) {
            $this->endRequest($requestId, 0, DaemonInterface::FCGI_UNKNOWN_ROLE);
            return;
        }
    }

    /**
     * Read a FastCGI name-value pair from a buffer and add it to the request params
     * 
     * @param int    $requestId The request id that sent the name-value pair
     * @param string $buffer    The buffer to read the pair from (pass by reference)
     */
    private function readNameValuePair($requestId, &$buffer)
    {
        $nameLength  = $this->readFieldLength($buffer);
        $valueLength = $this->readFieldLength($buffer);

        $contentFormat = (
            'a'.$nameLength.'name/'.
            'a'.$valueLength.'value/'
        );

        $content = unpack($contentFormat, $buffer);
        $this->requests[$requestId]['params'][$content['name']] = $content['value'];

        $buffer = substr($buffer, $nameLength + $valueLength);
    }

    /**
     * Read the field length of a FastCGI name-value pair from a buffer
     * 
     * @param string $buffer The buffer to read the field length from (pass by reference)
     * 
     * @return int The length of the field
     */
    private function readFieldLength(&$buffer)
    {
        $block  = unpack('C4', $buffer);

        $length = $block[1];
        $skip   = 1;

        if ($length & 0x80) {
            $fullBlock = unpack('N', $buffer);
            $length    = $fullBlock[1] & 0x7FFFFFFF;
            $skip      = 4;
        }

        $buffer = substr($buffer, $skip);

        return $length;
    }

    /**
     * End the request by writing an FCGI_END_REQUEST record and then removing
     * the request from memory and closing the connection if necessary.
     *
     * @param int $requestId      The request id to end
     * @param int $appStatus      The application status to declare
     * @param int $protocolStatus The protocol status to declare
     */
    private function endRequest($requestId, $appStatus = 0, $protocolStatus = DaemonInterface::FCGI_REQUEST_COMPLETE)
    {
        $content = pack('NCx3', $appStatus, $protocolStatus);
        $this->writeRecord($requestId, DaemonInterface::FCGI_END_REQUEST, $content);

        $keepAlive = $this->requests[$requestId]['keepAlive'];

        fclose($this->requests[$requestId]['stdin']);

        unset($this->requests[$requestId]);

        if (!$keepAlive) {
            $this->close();
        }
    }

    /**
     * Write a record to the connection.
     *
     * @param int    $requestId The request id to write to
     * @param int    $type      The type of record
     * @param string $content   The content of the record
     */
    private function writeRecord($requestId, $type, $content = null)
    {
        $contentLength = null === $content ? 0 : strlen($content);

        $headerData = pack('CCnnxx', DaemonInterface::FCGI_VERSION_1, $type, $requestId, $contentLength);

        $this->connection->write($headerData);

        if (null !== $content) {
            $this->connection->write($content);
        }
    }

    /**
     * Write a response to the connection as FCGI_STDOUT records.
     *
     * @param int             $requestId  The request id to write to
     * @param string          $headerData The header data to write (including terminating CRLFCRLF)
     * @param StreamInterface $stream     The stream to write
     */
    private function writeResponse($requestId, $headerData, StreamInterface $stream)
    {
        $data = $headerData;
        $eof  = false;

        $stream->rewind();

        do {
            $dataLength = strlen($data);

            if ($dataLength < 65535 && !$eof && !($eof = $stream->eof())) {
                $readLength  = 65535 - $dataLength;
                $data       .= $stream->read($readLength);
                $dataLength  = strlen($data);
            }

            $writeSize = min($dataLength, 65535);
            $writeData = substr($data, 0, $writeSize);
            $data      = substr($data, $writeSize);

            $this->writeRecord($requestId, DaemonInterface::FCGI_STDOUT, $writeData);
        } while ($writeSize === 65535);

        $this->writeRecord($requestId, DaemonInterface::FCGI_STDOUT);
    }

    /**
     * Dispatches a request to the kernel.
     *
     * @param int $requestId The request id that is ready to dispatch
     *
     * @throws DaemonException If there is an error dispatching the request
     */
    private function dispatchRequest($requestId)
    {
        $request = new Request(
            $this->requests[$requestId]['params'],
            $this->requests[$requestId]['stdin']
        );

        try {
            $response = $this->kernel->handleRequest($request);

            if ($response instanceof ResponseInterface) {
                $this->sendResponse($requestId, $response);
            } elseif ($response instanceof HttpFoundationResponse) {
                $this->sendHttpFoundationResponse($requestId, $response);
            } else {
                throw new \LogicException('Kernel must return a PSR-7 or HttpFoundation response message');
            }

            $this->endRequest($requestId);
        } catch (\Exception $exception) {
            $this->endRequest($requestId);

            throw $exception;
        }
    }

    /**
     * Sends the response to the client.
     *
     * @param int               $requestId The request id to respond to
     * @param ResponseInterface $response  The PSR-7 HTTP response message
     */
    private function sendResponse($requestId, ResponseInterface $response)
    {
        $statusCode   = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        $headerData = "Status: {$statusCode} {$reasonPhrase}\r\n";

        foreach ($response->getHeaders() as $name => $values) {
            $headerData .= $name.': '.implode(', ', $values)."\r\n";
        }

        $headerData .= "\r\n";

        $this->writeResponse($requestId, $headerData, $response->getBody());
    }

    /**
     * Send a HttpFoundation response to the client.
     * 
     * @param int                    $requestId The request id to respond to
     * @param HttpFoundationResponse $response  The HTTP foundation response message
     */
    private function sendHttpFoundationResponse($requestId, HttpFoundationResponse $response)
    {
        $statusCode = $response->getStatusCode();

        $headerData  = "Status: {$statusCode}\r\n";
        $headerData .= $response->headers . "\r\n";

        $stream = new Stream('php://memory', 'r+');
        $stream->write($response->getContent());
        $stream->rewind();

        $this->writeResponse($requestId, $headerData, $stream);
    }
}
