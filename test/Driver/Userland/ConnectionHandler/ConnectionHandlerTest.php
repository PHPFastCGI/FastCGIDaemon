<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandler;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\ConnectionException;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Client\ConnectionWrapper;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zend\Diactoros\Response;

/**
 * Tests that the connection handler is correctly handling FastCGI connections.
 */
class ConnectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the daemon is correctly handling requests and PSR-7 and
     * HttpFoundation responses with large param records and message bodies.
     */
    public function testHandler()
    {
        $testData = $this->createTestData();

        // Create test environment
        $callbackGenerator = function ($response) use ($testData) {
            return function (RequestInterface $request) use ($response, $testData) {
                $this->assertEquals($testData['requestParams'], $request->getParams());
                $this->assertEquals($testData['requestBody'],   stream_get_contents($request->getStdin()));

                return $response;
            };
        };

        $scenarios = [
            ['responseKey' => 'symfonyResponse', 'rawResponseKey' => 'rawSymfonyResponse'],
            ['responseKey' => 'psr7Response',    'rawResponseKey' => 'rawPsr7Response'],
        ];

        foreach ($scenarios as $scenario) {
            $callback = $callbackGenerator($testData[$scenario['responseKey']]);
            $context  = $this->createTestingContext($callback);

            $requestId = 1;

            $context['clientWrapper']->writeRequest($requestId, $testData['requestParams'], $testData['requestBody']);

            do {
                $context['handler']->ready();
            } while (!$context['connection']->isClosed());

            $rawResponse         = $context['clientWrapper']->readResponse($this, $requestId);
            $expectedRawResponse = $testData[$scenario['rawResponseKey']];

            // Check response
            $this->assertEquals(strlen($expectedRawResponse), strlen($rawResponse));
            $this->assertEquals($expectedRawResponse, $rawResponse);

            // Clean up
            fclose($context['sockets'][0]);
        }
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
        try {
            $context['handler']->ready();
            $this->fail('Should have thrown ConnectionException');
        } catch (ConnectionException $exception) {
        }

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());
    }

    /**
     * Test that the daemon doesn't accept new requests once it has been
     * shutdown but still handles old ones.
     */
    public function testShutdown()
    {
        $response = new Response($this->toStream('Hello World'), 200);

        $kernelCalled = false;

        // Create test environment
        $context = $this->createTestingContext(function (RequestInterface $request) use ($response, &$kernelCalled) {
            $this->assertEquals(['FOO' => 'bar'], $request->getParams());

            $kernelCalled = true;

            return $response;
        });

        // Write half of the first request (id 0)
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, DaemonInterface::FCGI_KEEP_CONNECTION);
        $context['clientWrapper']->writeParamsRecord(0, ['foo' => 'bar']);
        $context['clientWrapper']->writeParamsRecord(0);

        // Process first half of the first request
        $context['handler']->ready();

        // Trigger the shutdown method
        $context['handler']->shutdown();

        // Try creating a new request (id 1)
        $context['clientWrapper']->writeBeginRequestRecord(1, DaemonInterface::FCGI_RESPONDER, DaemonInterface::FCGI_KEEP_CONNECTION);

        // Process the attempt at a second request after the shutdown
        $context['handler']->ready();

        // The application should end the second request immediately
        $record = $context['clientWrapper']->readRecord($this);
        $this->assertEquals(DaemonInterface::FCGI_END_REQUEST, $record['type']);
        $this->assertEquals(1, $record['requestId']);

        // The application should declare an overloaded protocol status
        $content = unpack('NappStatus/CprotocolStatus/x3', $record['contentData']);
        $this->assertEquals(DaemonInterface::FCGI_OVERLOADED, $content['protocolStatus']);

        // Check daemon hasn't closed server side connection
        $this->assertFalse($context['connection']->isClosed());

        // Write the second half of the first request
        $context['clientWrapper']->writeStdinRecord(0);

        // Process the second half of the first request
        $context['handler']->ready();

        // Assert that kernel was called
        $this->assertTrue($kernelCalled);

        // Receive response
        $rawResponse = '';

        do {
            $record = $context['clientWrapper']->readRecord($this);

            $this->assertEquals(0, $record['requestId']);

            if (DaemonInterface::FCGI_STDOUT === $record['type']) {
                $rawResponse .= $record['contentData'];
            }
        } while (DaemonInterface::FCGI_END_REQUEST !== $record['type']);

        $expectedResponse = "Status: 200 OK\r\n\r\nHello World";

        // Check response
        $this->assertEquals($expectedResponse, $rawResponse);

        // Close the client side socket
        fclose($context['sockets'][0]);
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
        try {
            $context['handler']->ready();
            $this->fail('Should have thrown LogicException');
        } catch (\LogicException $exception) {
        }

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

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
     * Test that the daemon correctly handles an abort request record with a
     * request id that hasn't been initiated.
     */
    public function testInvalidRequestId()
    {
        $context = $this->createTestingContext();

        // Write an abort request record without beginning a request
        $context['clientWrapper']->writeAbortRequestRecord(0);

        try {
            $context['handler']->ready();
            $this->fail('Should have thrown ProtocolException');
        } catch (ProtocolException $exception) {
            $this->assertEquals('Invalid request id for record of type: '.DaemonInterface::FCGI_ABORT_REQUEST, $exception->getMessage());
        }

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

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
        $context['clientWrapper']->writeBeginRequestRecord(0, DaemonInterface::FCGI_RESPONDER, 0);
        $context['clientWrapper']->writeRecord(DaemonInterface::FCGI_STDOUT, 0);

        try {
            $context['handler']->ready();
            $this->fail('Should have thrown ProtocolException');
        } catch (ProtocolException $exception) {
            $this->assertEquals('Unexpected packet of type: '.DaemonInterface::FCGI_STDOUT, $exception->getMessage());
        }

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

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

        try {
            $context['handler']->ready();
            $this->fail('Should have thrown ProtocolException');
        } catch (ProtocolException $exception) {
            $this->assertEquals('Unexpected FCGI_BEGIN_REQUEST record', $exception->getMessage());
        }

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

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
        $this->assertEquals(DaemonInterface::FCGI_END_REQUEST, $record['type']);

        // The application should declare an unknown role protocol status
        $content = unpack('NappStatus/CprotocolStatus/x3', $record['contentData']);
        $this->assertEquals(DaemonInterface::FCGI_UNKNOWN_ROLE, $content['protocolStatus']);

        // Check daemon has closed server side connection
        $this->assertTrue($context['connection']->isClosed());

        // Close the client side socket
        fclose($context['sockets'][0]);
    }

    /**
     * Converts a string to a stream resource and places pointer at the start.
     *
     * @param string $string
     *
     * @return resource
     */
    private function toStream($string)
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
    private function createStreamSocketPair()
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
    private function createTestingContext($callback = false)
    {
        $sockets       = $this->createStreamSocketPair();
        $clientWrapper = new ConnectionWrapper($sockets[0]);
        $connection    = new StreamSocketConnection($sockets[1]);
        $handler       = new ConnectionHandler(new MockKernel(['handleRequest' => $callback]), $connection);

        return [
            'sockets'       => $sockets,
            'clientWrapper' => $clientWrapper,
            'connection'    => $connection,
            'handler'       => $handler,
        ];
    }

    /**
     * Create test request data.
     *
     * @return array
     */
    private function createTestData()
    {
        $testData = [
            'requestBody'   => str_repeat('X', 100000),
            'requestParams' => [
                str_repeat('A', 10)  => str_repeat('b', 127),
                str_repeat('C', 128) => str_repeat('d', 256),
                str_repeat('E', 520) => str_repeat('f', 50),
            ],
            'responseStatusCode' => 201,
            'responseBody'       => str_repeat('Y', 100000),
            'responseHeaders'    => ['Header-1' => 'foo', 'Header-2' => 'bar'],
        ];

        $testData['responseBodyStream'] = $this->toStream($testData['responseBody']);

        $testData['symfonyResponse'] = new HttpFoundationResponse($testData['responseBody'], $testData['responseStatusCode'], $testData['responseHeaders']);
        $testData['psr7Response']    = new Response($testData['responseBodyStream'], $testData['responseStatusCode'], $testData['responseHeaders']);

        $testData['rawSymfonyResponse']  = "Status: 201\r\n";
        $testData['rawSymfonyResponse'] .= $testData['symfonyResponse']->headers."\r\n";
        $testData['rawSymfonyResponse'] .= $testData['responseBody'];

        $testData['rawPsr7Response'] = 'Status: 201 '.$testData['psr7Response']->getReasonPhrase()."\r\n";
        foreach ($testData['psr7Response']->getHeaders() as $name => $value) {
            $testData['rawPsr7Response'] .=  $name.': '.implode(', ', $value)."\r\n";
        }
        $testData['rawPsr7Response'] .= "\r\n".$testData['responseBody'];

        return $testData;
    }
}
