<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;

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
}
