<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;
use PHPFastCGI\Test\FastCGIDaemon\ConnectionHandler\CallableConnectionHandlerFactory;

/**
 * Test to ensure that the StreamSocketConnectionPool class can accept new
 * connections and trigger updates when data is sent.
 */
class StreamSocketConnectionPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test basic connection pool.
     */
    public function testConnectionPool()
    {
        $address = 'tcp://localhost:7000';

        $serverSocket = stream_socket_server($address);
        $connectionPool = new StreamSocketConnectionPool($serverSocket);

        $clientSocket = stream_socket_client($address);
        $connection = new StreamSocketConnection($clientSocket);

        $closed = false;

        $connectionHandlerCallback = function ($method, ConnectionInterface $connection) use (&$closed) {
            if ('ready' === $method) {
                try {
                    $command = $connection->read(1024);

                    if ('ping' === $command) {
                        $connection->write('pong');
                    }
                } catch (ConnectionException $exception) {
                    $connection->close();
                    $closed = true;
                }
            } else {
                throw new \LogicException('Unexpected method');
            }
        };
        $connectionHandlerFactory = new CallableConnectionHandlerFactory($connectionHandlerCallback);

        // Should accept connection
        $connectionPool->operate($connectionHandlerFactory, 1);

        // Ping pong test
        $connection->write('ping');
        $connectionPool->operate($connectionHandlerFactory, 1);
        $this->assertEquals('pong', $connection->read(1024));

        // Close test
        $connection->close();
        $connectionPool->operate($connectionHandlerFactory, 1);
        $this->assertTrue($closed);
    }

    /**
     * Test basic stream_select fail.
     * 
     * @expectedException \RuntimeException
     */
    public function testStreamSelectFail()
    {
        $address = 'tcp://localhost:7000';
        $serverSocket = stream_socket_server($address);
        $connectionPool = new StreamSocketConnectionPool($serverSocket);

        fclose($serverSocket);

        $connectionHandlerFactory = new CallableConnectionHandlerFactory(function () {});

        $connectionPool->operate($connectionHandlerFactory, 1);
    }

    /**
     * Test stream_select signal interrupt doesn't trigger \RunTime exception
     */
    public function testStreamSelectSignalInterrupt()
    {
        $address = 'tcp://localhost:7000';
        $serverSocket = stream_socket_server($address);
        $connectionPool = new StreamSocketConnectionPool($serverSocket);

        $connectionHandlerFactory = new CallableConnectionHandlerFactory(function () {});

        $alarmCalled = false;

        pcntl_signal(SIGALRM, function () use (&$alarmCalled) {
            $alarmCalled = true;
        });

        pcntl_alarm(1);

        $connectionPool->operate($connectionHandlerFactory, 1);

        $this->assertTrue($alarmCalled);
    }

    /**
     * Test shutdown.
     */
    public function testShutdown()
    {
        $address = 'tcp://localhost:7000';

        $serverSocket = stream_socket_server($address);
        $connectionPool = new StreamSocketConnectionPool($serverSocket);

        // Create connection and prompt read
        $clientSocket = stream_socket_client($address);
        $connection = new StreamSocketConnection($clientSocket);
        $connection->write('hello');

        $shutdownCalled   = false;
        $readCalled = false;

        $connectionHandlerCallback = function ($method, ConnectionInterface $connection) use (&$shutdownCalled, &$readCalled) {
            if ('shutdown' === $method) {
                $shutdownCalled = true;
            } elseif ('ready' === $method) {
                $readCalled = true;
                $connection->close();
            }   
        };
        $connectionHandlerFactory = new CallableConnectionHandlerFactory($connectionHandlerCallback);

        // Should accept connection
        $connectionPool->operate($connectionHandlerFactory, 1);

        // Shutdown connection pool
        $connectionPool->shutdown();

        // Make sure the handler cleaned up properly and got a chance to read the data
        $this->assertTrue($shutdownCalled);
        $this->assertTrue($readCalled);
    }
}
