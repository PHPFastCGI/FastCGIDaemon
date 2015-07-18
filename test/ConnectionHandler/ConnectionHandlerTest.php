<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandler;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\Test\FastCGIDaemon\Client\ConnectionWrapper;
use PHPFastCGI\Test\FastCGIDaemon\Logger\InMemoryLogger;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

/**
 * Tests that the connection handler is correctly handling FastCGI connections.
 */
class ConnectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Converts a string to a stream resource and places pointer at the start.
     *
     * @param string $string
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
     * Create a testing context.
     *
     * @param callable $callback
     *
     * @return array
     */
    protected function createTestingContext($callback = null)
    {
        if (null === $callback) {
            $callback = function () {};
        }

        $sockets       = $this->createStreamSocketPair();
        $clientWrapper = new ConnectionWrapper($sockets[0]);
        $connection    = new StreamSocketConnection($sockets[1]);
        $handler       = new ConnectionHandler(new CallbackWrapper($callback), $connection);
        $logger        = new InMemoryLogger();

        $handler->setLogger($logger);

        return [
            'sockets'       => $sockets,
            'clientWrapper' => $clientWrapper,
            'connection'    => $connection,
            'handler'       => $handler,
            'logger'        => $logger,
        ];
    }

    /**
     * Test that the daemon is correctly handling requests with large param
     * records and message bodies.
     */
    public function testLargeParamsAndBody()
    {
        // Set up body streams
        $body       = str_repeat('X', 100000);
        $bodyStream = $this->toStream($body);

        // Set up test request values
        $serverParams = [
            str_repeat('A', 10)  => str_repeat('b', 127),
            str_repeat('C', 128) => str_repeat('d', 256),
            str_repeat('E', 520) => str_repeat('f', 50),
        ];

        $response = new Response($bodyStream, 200, ['Header-1' => 'foo', 'Header-2' => 'bar']);

        // Create test environment
        $context = $this->createTestingContext(function (ServerRequestInterface $request) use ($serverParams, $body, $response) {
            $this->assertEquals($serverParams, $request->getServerParams());
            $this->assertEquals($body, (string) $request->getBody());

            return $response;
        });

        // Send request
        $requestId = 5;

        $context['clientWrapper']->writeBeginRequestRecord($requestId, DaemonInterface::FCGI_RESPONDER, 0);

        foreach ($serverParams as $name => $value) {
            $context['clientWrapper']->writeParamsRecord($requestId, $name, $value);
        }

        $context['clientWrapper']->writeParamsRecord($requestId);

        $chunks = str_split($body, 65535);

        foreach ($chunks as $chunk) {
            $context['clientWrapper']->writeStdinRecord($requestId, $chunk);
        }

        $context['clientWrapper']->writeStdinRecord($requestId);

        // Trigger Handler
        do {
            $context['handler']->ready();
        } while (!$context['connection']->isClosed());

        // Receive response
        $rawResponse = '';

        do {
            $record = $context['clientWrapper']->readRecord($this);

            $this->assertEquals($requestId, $record['requestId']);

            if (DaemonInterface::FCGI_STDOUT === $record['type']) {
                $rawResponse .= $record['contentData'];
            }
        } while (DaemonInterface::FCGI_END_REQUEST !== $record['type']);

        $expectedRawResponse = 'Status: '.$response->getStatusCode().' '.$response->getReasonPhrase()."\r\n";

        foreach ($response->getHeaders() as $name => $values) {
            $expectedRawResponse .= $name.': '.implode(', ', $values)."\r\n";
        }

        $expectedRawResponse .= "\r\n".$body;

        // Check response
        $this->assertEquals(strlen($expectedRawResponse), strlen($rawResponse));
        $this->assertEquals($expectedRawResponse, $rawResponse);

        // Clean up
        fclose($bodyStream);
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon detects when the client has prematurely closed the
     * connection.
     */
    public function testClosedConnection()
    {
        $context = $this->createTestingContext();

        // Close the client connection
        fclose($context['sockets'][0]);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());
    }

    /**
     * Test that the daemon correctly handles a kernel exception.
     */
    public function testKernelExceptionHandling()
    {
        // Create a broken kernel that does not return a valid response (triggering an exception)
        $context = $this->createTestingContext(function () { return false; });

        // Write a simple request
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, 0);
        $context['clientWrapper']->writeParamsRecord(0);
        $context['clientWrapper']->writeStdinRecord(0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Kernel must return a PSR-7 HTTP response message',
                'context' => [],
            ],
        ];
        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon correctly handles an abort request record.
     */
    public function testAbortRequestRecord()
    {
        $context = $this->createTestingContext();

        // Write an abort request record and close the connection
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, 0);
        $context['clientWrapper']->writeAbortRequestRecord(0);

        // Run the handler
        $context['handler']->ready();

        // The application should respond with an end request record
        $record = $context['clientWrapper']->readRecord($this);
        $this->assertEquals(DaemonInterface::FCGI_END_REQUEST, $record['type']);

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon correctly handles an unexpected abort request record.
     */
    public function testUnexpectedAbortRequestRecord()
    {
        $context = $this->createTestingContext();

        // Write an abort request record without beginning a request
        $context['clientWrapper']->writeAbortRequestRecord(0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Unexpected FCG_ABORT_REQUEST record',
                'context' => [],
            ],
        ];
        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon correctly handles an unexpected record.
     */
    public function testUnexpectedRecord()
    {
        $context = $this->createTestingContext();

        // Write a record with a type that makes no sense (application doesn't receive stdout)
        $context['clientWrapper']->writeRecord(DaemonInterface::FCGI_STDOUT, 0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Unexpected packet of unkown type: '.DaemonInterface::FCGI_STDOUT,
                'context' => [],
            ],
        ];
        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon throws an error if a begin request record is
     * received with the same id as an ongoing request.
     */
    public function testUnexpectedBeginRequestRecord()
    {
        $context = $this->createTestingContext();

        // Write two begin request records with the same id
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, 0);
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, 0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Unexpected FCGI_BEGIN_REQUEST record',
                'context' => [],
            ],
        ];

        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon throws an error if a stdin record is received for
     * an unknown request id.
     */
    public function testUnexpectedStdinRecord()
    {
        $context = $this->createTestingContext();

        // Write an unexpected record and close the connection
        $context['clientWrapper']->writeStdinRecord(0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Unexpected FCGI_STDIN record',
                'context' => [],
            ],
        ];
        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon throws an error if a params record is received for
     * an unknown request id.
     */
    public function testUnexpectedParamsRecord()
    {
        $context = $this->createTestingContext();

        // Write an unexpected record and close the connection
        $context['clientWrapper']->writeParamsRecord(0);

        // Run the handler
        $context['handler']->ready();

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Check the logging has worked
        $expectedLogMessages = [
            [
                'level'   => 'error',
                'message' => 'Unexpected FCGI_PARAMS record',
                'context' => [],
            ],
        ];
        $this->assertEquals($expectedLogMessages, $context['logger']->getMessages());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Test that the daemon responds correctly to a non-responder role.
     */
    public function testUnknownRole()
    {
        $context = $this->createTestingContext();

        // Write an unexpected record and close the connection
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_FILTER, 0);

        // Run the handler
        $context['handler']->ready();

        // The application should respond with an end request record
        $record = $context['clientWrapper']->readRecord($this);
        $this->assertEquals(DaemonInterface::FCGI_END_REQUEST,  $record['type']);

        // The application should declare an unknown role protocol status
        $content = unpack('NappStatus/CprotocolStatus/x3', $record['contentData']);
        $this->assertEquals(DaemonInterface::FCGI_UNKNOWN_ROLE, $content['protocolStatus']);

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }
}
