<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnection;

class StreamSocketConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $streamSocket1 = new StreamSocketConnection($sockets[0]);
        $streamSocket2 = new StreamSocketConnection($sockets[1]);

        $streamSocket1->write('foobar');
        $this->assertEquals('foo', $streamSocket2->read('3'));
        $this->assertEquals('bar', $streamSocket2->read('3'));

        $streamSocket2->write('barfoo');
        $this->assertEquals('bar', $streamSocket1->read('3'));
        $this->assertEquals('foo', $streamSocket1->read('3'));

        $streamSocket1->close();
        $streamSocket2->close();
    }
}
