<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnection;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\ConnectionException;

/**
 * Test to ensure that the StreamSocketConnection class can read, write and
 * close.
 */
class StreamSocketConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the StreamSocketConnection class can read, write and close.
     */
    public function testConnection()
    {
        // Create sockets and connection classes
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $streamSocket1 = new StreamSocketConnection($sockets[0]);
        $streamSocket2 = new StreamSocketConnection($sockets[1]);

        // Check writing and reading from 1 to 2
        $streamSocket1->write('foobar');
        $this->assertEquals('foo', $streamSocket2->read('3'));
        $this->assertEquals('bar', $streamSocket2->read('3'));

        // Check writing and reading from 2 to 1
        $streamSocket2->write('barfoo');
        $this->assertEquals('bar', $streamSocket1->read('3'));
        $this->assertEquals('foo', $streamSocket1->read('3'));

        // Check that they close
        $streamSocket1->close();
        $streamSocket2->close();
        $this->assertTrue($streamSocket1->isClosed());
        $this->assertTrue($streamSocket2->isClosed());
    }

    /**
     * Tests that the StreamSocketConnection class can handle errors.
     */
    public function testClosedConnections()
    {
        // Create sockets and connection classes
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $streamSocket1 = new StreamSocketConnection($sockets[0]);
        $streamSocket2 = new StreamSocketConnection($sockets[1]);

        // Check reading nothing
        $this->assertEquals('', $streamSocket2->read(0));

        $streamSocket1->close();
        $streamSocket2->close();

        try {
            $streamSocket1->read(0);
            $this->fail('Should have thrown exception');
        } catch (ConnectionException $exception) {
            $this->assertEquals('Connection has been closed', $exception->getMessage());
        }

        try {
            $streamSocket2->write('hello');
            $this->fail('Should have thrown exception');
        } catch (ConnectionException $exception) {
            $this->assertEquals('Connection has been closed', $exception->getMessage());
        }
    }

    /**
     * Tests that the StreamSocketConnection class can handle errors.
     */
    public function testFailedWrite()
    {
        // Create sockets and connection classes
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $streamSocket1 = new StreamSocketConnection($sockets[0]);
        $streamSocket2 = new StreamSocketConnection($sockets[1]);

        $streamSocket2->close();

        try {
            $streamSocket1->write('hello');
            $streamSocket1->write('bo');
            $streamSocket1->write('bo');
            $this->fail('Should have thrown exception');
        } catch (ConnectionException $exception) {
            $this->assertEquals('fwrite failed', $exception->getMessage());
        }

        $streamSocket1->close();
    }

    /**
     * Tests that the StreamSocketConnection class can handle errors.
     */
    public function testFailedRead()
    {
        // Create sockets and connection classes
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $streamSocket1 = new StreamSocketConnection($sockets[0]);
        $streamSocket2 = new StreamSocketConnection($sockets[1]);

        $streamSocket2->close();

        try {
            $streamSocket1->read(5);
            $this->fail('Should have thrown exception');
        } catch (ConnectionException $exception) {
            $this->assertEquals('fread failed', $exception->getMessage());
        }

        $streamSocket1->close();
    }
}
