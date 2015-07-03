<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandler;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\Test\FastCGIDaemon\Client\ConnectionWrapper;
use PHPFastCGI\Test\FastCGIDaemon\Mock\KernelMock;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

/**
 * Tests that the connection handler is correctly handling FastCGI connections.
 */
class ConnectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Converts a string to a stream resource and places pointer at the start.
     * 
     * @param  string $string
     * 
     * @return resource
     */
    protected function toStream($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        rewind($stream);
        return $stream;
    }

    /**
     * Creates a pair of non-blocking stream socket resources.
     * 
     * @return resource[]
     */
    protected function createStreamSocketPair()
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);
        return $sockets;
    }

    /**
     * Test that the daemon is correctly handling requests with large param
     * records and message bodies.
     */
    public function testRequestLimits()
    {
        // Set up body streams
        $string = str_repeat('X', 100000);
        $requestBodyStream  = $this->toStream($string);
        $responseBodyStream = $this->toStream($string);

        // Set up kernel
        $serverParams = [
            str_repeat('A', 10)  => str_repeat('b', 127),
            str_repeat('C', 128) => str_repeat('d', 256),
            str_repeat('E', 520) => str_repeat('f', 50),
        ];

        $expectedRequest = new ServerRequest($serverParams, [], null, null, $requestBodyStream);
        $response        = new Response($responseBodyStream, 200, ['Header-1: foo', 'Header-2: bar']);
        $kernelMock      = new KernelMock($this, $expectedRequest, $response);

        // Create connections, set up client wrapper and daemon handler
        $sockets       = $this->createStreamSocketPair();
        $clientWrapper = new ConnectionWrapper($sockets[0]);
        $connection    = new StreamSocketConnection($sockets[1]);
        $handler       = new ConnectionHandler($kernelMock, $connection);

        // Send request
        $requestId = 5;

        $clientWrapper->writeBeginRequestRecord($requestId, DaemonInterface::FCGI_RESPONDER, 0);

        foreach ($expectedRequest->getServerParams() as $name => $value) {
            $clientWrapper->writeParamsRecord($requestId, $name, $value);
        }

        $clientWrapper->writeParamsRecord($requestId);

        $requestBody = $expectedRequest->getBody();

        $chunks = str_split($requestBody, 65535);

        foreach ($chunks as $chunk) {
            $clientWrapper->writeStdinRecord($requestId, $chunk);
        }

        $clientWrapper->writeStdinRecord($requestId);

        // Trigger Handler
        do {
            $handler->ready();
        } while (!$connection->isClosed());

        // Receive response
        $responseBody = '';

        do {
            $record = $clientWrapper->readRecord($this);

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

        // Check response
        $this->assertEquals(strlen($expectedResponseBody), strlen($responseBody));
        $this->assertEquals($expectedResponseBody, $responseBody);

        // Clean up
        fclose($requestBodyStream);
        fclose($responseBodyStream);

        fclose($sockets[0]);
    }

    /**
     * Test that the daemon detects when the client has prematurely closed the
     * connection.
     */
    public function testClosedConnection()
    {
        $sockets    = $this->createStreamSocketPair();
        $connection = new StreamSocketConnection($sockets[1]);

        // Set up handler
        $kernelMock = $this->getMockBuilder('PHPFastCGI\FastCGIDaemon\KernelInterface')->getMock();
        $handler    = new ConnectionHandler($kernelMock, $connection);

        // Close the client connection
        fclose($sockets[0]);

        // Run the handler
        $handler->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($connection->isClosed());
    }

    /**
     * Test that the daemon detects when the client does not abide by the
     * protocol and closes the connection accordingly.
     */
    public function testInvalidProtocol()
    {
        // Mock the kernel (just needs to pass as a parameter)
        $kernelMock = $this->getMockBuilder('PHPFastCGI\FastCGIDaemon\KernelInterface')->getMock();

        // Create connections, client side wrapper and daemon side handler
        $sockets       = $this->createStreamSocketPair();
        $clientWrapper = new ConnectionWrapper($sockets[0]);
        $connection    = new StreamSocketConnection($sockets[1]);
        $handler       = new ConnectionHandler($kernelMock, $connection);

        // Write an unexpected record and close the connection
        $clientWrapper->writeParamsRecord(0);
        fclose($sockets[0]);

        // Run the handler
        $handler->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($connection->isClosed());
    }
}
