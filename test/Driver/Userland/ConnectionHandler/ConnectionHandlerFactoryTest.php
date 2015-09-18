<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactory;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;

/**
 * Tests the daemon.
 */
class ConnectionHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the factory creates a connection handler from a kernel.
     */
    public function testCreatesConnectionHandlerFromKernel()
    {
        $kernel = new MockKernel;

        $connection = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\Driver\\Userland\\Connection\\StreamSocketConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $connectionHandlerFactory = new ConnectionHandlerFactory;

        $connectionHandler = $connectionHandlerFactory->createConnectionHandler($kernel, $connection);

        $this->assertInstanceOf('PHPFastCGI\\FastCGIDaemon\\Driver\\Userland\\ConnectionHandler\\ConnectionHandler', $connectionHandler);
    }
}
