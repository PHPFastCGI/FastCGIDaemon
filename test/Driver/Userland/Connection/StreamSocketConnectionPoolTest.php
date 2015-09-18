<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnectionPool;

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
        $clientConnection = new StreamSocketConnection($clientSocket);

        // Should accept connection
        $initialReadableConnections = $connectionPool->getReadableConnections(0);
        $this->assertCount(0, $initialReadableConnections);

        // Ping test
        $clientConnection->write('ping');
        $readableConnections = $connectionPool->getReadableConnections(0);
        $this->assertCount(1, $readableConnections);
        $serverConnection = array_pop($readableConnections);
        $this->assertEquals('ping', $serverConnection->read(4));

        // Close test
        $serverConnection->close();
        $finalReadableConnections = $connectionPool->getReadableConnections(0);
        $this->assertCount(0, $finalReadableConnections);
        $this->assertEquals(0, $connectionPool->count());
        $clientConnection->close();

        $connectionPool->close();
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

        $connectionPool->getReadableConnections(0);
    }

    /**
     * Test stream_select signal interrupt doesn't trigger \RunTime exception
     */
    public function testStreamSelectSignalInterrupt()
    {
        $address = 'tcp://localhost:7000';
        $serverSocket = stream_socket_server($address);
        $connectionPool = new StreamSocketConnectionPool($serverSocket);

        $alarmCalled = false;

        declare(ticks = 1);

        pcntl_signal(SIGALRM, function () use (&$alarmCalled) {
            $alarmCalled = true;
        });

        pcntl_alarm(1);

        $connectionPool->getReadableConnections(2);

        $this->assertTrue($alarmCalled);
    }
}
