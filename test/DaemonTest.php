<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Daemon;

/**
 * Tests the daemon.
 */
class DaemonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the daemon runs a connection pool with a handler factory.
     */
    public function testRun()
    {
        $mockConnectionHandlerFactory = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\ConnectionHandler\\ConnectionHandlerFactory')
            ->disableOriginalConstructor()
            ->setMethods(['setLogger'])
            ->getMock();

        $mockConnectionHandlerFactory
            ->expects($this->once())
            ->method('setLogger');

        $mockConnectionPool = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\Connection\\StreamSocketConnectionPool')
            ->disableOriginalConstructor()
            ->setMethods(['operate'])
            ->getMock();

        $mockConnectionPool
            ->expects($this->once())
            ->method('operate')
            ->with($this->equalTo($mockConnectionHandlerFactory), $this->equalTo(5));

        $daemon = new Daemon($mockConnectionPool, $mockConnectionHandlerFactory);
        $daemon->run();
    }
}
