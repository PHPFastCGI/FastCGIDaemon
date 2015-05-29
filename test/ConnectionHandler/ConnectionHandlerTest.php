<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandler;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\Test\FastCGIDaemon\KernelMock;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class ConnectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected function readRecord($stream)
    {
        $headerData = fread($stream, 8);
        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

        $this->assertEquals(8, strlen($headerData));

        $record = unpack($headerFormat, $headerData);

        if ($record['contentLength'] > 0) {
            $record['contentData'] = '';

            do {
                $block = fread($stream, $record['contentLength'] - strlen($record['contentData']));

                $record['contentData'] .= $block;
            } while (strlen($block) > 0 && strlen($record['contentData']) !== $record['contentLength']);

            $this->assertEquals($record['contentLength'], strlen($record['contentData']));
        } else {
            $record['contentData'] = '';
        }

        if ($record['paddingLength'] > 0) {
            fread($stream, $record['paddingLength']);
        }

        return $record;
    }

    protected function writeRecord($stream, $type, $requestId, $content = '', $paddingLength = 0)
    {
        $header  = pack('CCnnCx', DaemonInterface::FCGI_VERSION_1, $type, $requestId, strlen($content), $paddingLength);
        $padding = pack('x'.$paddingLength);

        fwrite($stream, $header);
        fwrite($stream, $content);
        fwrite($stream, $padding);
    }

    protected function writeBeginRequestRecord($stream, $requestId, $role, $flags)
    {
        $content = pack('nCx5', $role, $flags);
        $this->writeRecord($stream, DaemonInterface::FCGI_BEGIN_REQUEST, $requestId, $content);
    }

    protected function writeParamsRecord($stream, $requestId, $name = null, $value = null)
    {
        if (null === $name && null === $value) {
            $this->writeRecord($stream, DaemonInterface::FCGI_PARAMS, $requestId);

            return;
        }

        $content = '';

        $addLength = function ($parameter) use (&$content) {
            $parameterLength = strlen($parameter);

            if ($parameterLength > 0x7F) {
                $content .= pack('N', $parameterLength | 0x80000000);
            } else {
                $content .= pack('C', $parameterLength);
            }
        };

        $addLength($name);
        $addLength($value);

        $content .= $name;
        $content .= $value;

        $contentLength = strlen($content);
        $paddingLength = ((int) ceil(((float) $contentLength) / 8.0) * 8) - $contentLength;

        $this->writeRecord($stream, DaemonInterface::FCGI_PARAMS, $requestId, $content, $paddingLength);
    }

    protected function writeStdinRecord($stream, $requestId, $content = '')
    {
        $this->writeRecord($stream, DaemonInterface::FCGI_STDIN, $requestId, $content);
    }

    protected function toStream($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        rewind($stream);
        return $stream;
    }

    public function testRequest()
    {
        $string = str_repeat('X', 100000);
        $requestBodyStream  = $this->toStream($string);
        $responseBodyStream = $this->toStream($string);

        // Set up kernel
        $expectedRequest = new ServerRequest(
            [ // Server
                str_repeat('A', 10)  => str_repeat('b', 127),
                str_repeat('C', 128) => str_repeat('d', 256),
                str_repeat('E', 520) => str_repeat('f', 50),
            ],
            [], null, null, $requestBodyStream
        );

        $response = new Response($responseBodyStream, 200, ['Header-1: foo', 'Header-2: bar']);

        $kernelMock = new KernelMock($this, $expectedRequest, $response);

        // Create connections
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $stream     = $sockets[0];
        $connection = new StreamSocketConnection($sockets[1]);

        // Set up handler
        $handler = new ConnectionHandler($kernelMock, $connection);

        // Send request
        $requestId = 5;

        $this->writeBeginRequestRecord($stream, $requestId, DaemonInterface::FCGI_RESPONDER, 0);

        foreach ($expectedRequest->getServerParams() as $name => $value) {
            $this->writeParamsRecord($stream, $requestId, $name, $value);
        }

        $this->writeParamsRecord($stream, $requestId);

        $requestBody = $expectedRequest->getBody();

        $chunks = str_split($requestBody, 65535);

        foreach ($chunks as $chunk) {
            $this->writeStdinRecord($stream, $requestId, $chunk);
        }

        $this->writeStdinRecord($stream, $requestId);

        // Trigger Handler
        do {
            $handler->ready();
        } while (!$connection->isClosed());

        // Receive response
        $responseBody = '';

        do {
            $record = $this->readRecord($stream);

            $this->assertEquals($requestId, $record['requestId']);

            if (DaemonInterface::FCGI_STDOUT === $record['type']) {
                $responseBody .= $record['contentData'];
            }
        } while (DaemonInterface::FCGI_END_REQUEST !== $record['type']);

        $expectedResponseBody = 'Status: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . "\r\n";

        foreach ($response->getHeaders() as $name => $values) {
            $expectedResponseBody .= $name . ': ' . implode(', ', $values) . "\r\n";
        }

        $expectedResponseBody .= "\r\n" . (string) $response->getBody();

        $this->assertEquals(strlen($expectedResponseBody), strlen($responseBody));
        $this->assertEquals($expectedResponseBody, $responseBody);

        fclose($stream);
        fclose($requestBodyStream);
        fclose($responseBodyStream);
    }

    public function testClosedConnection()
    {
        // Create connections
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $stream     = $sockets[0];
        $connection = new StreamSocketConnection($sockets[1]);

        // Set up handler
        $kernelMock = $this->getMockBuilder('PHPFastCGI\FastCGIDaemon\KernelInterface')->getMock();
        $handler = new ConnectionHandler($kernelMock, $connection);

        fclose($stream);

        $handler->ready();
        $this->assertTrue($connection->isClosed());
    }

    public function testInvalidProtocol()
    {
        // Create connections
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $stream     = $sockets[0];
        $connection = new StreamSocketConnection($sockets[1]);

        // Set up handler
        $kernelMock = $this->getMockBuilder('PHPFastCGI\FastCGIDaemon\KernelInterface')->getMock();
        $handler = new ConnectionHandler($kernelMock, $connection);

        $this->writeParamsRecord($stream, 0);
        fclose($stream);

        $handler->ready();
        $this->assertTrue($connection->isClosed());
    }
}
