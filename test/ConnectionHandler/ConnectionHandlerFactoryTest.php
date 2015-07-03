<?php

namespace PHPFastCGI\Test\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;

/**
 * Tests the daemon.
 */
class ConnectionHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the factory creates a connection handler from a kernel
     */
    public function testCreatesConnectionHandlerFromKernel()
    {
        $kernel = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\KernelInterface')
            ->getMock();

        $connectionHandlerFactory = new ConnectionHandlerFactory($kernel);

        $connection = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\Connection\\StreamSocketConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $connectionHandler = $connectionHandlerFactory->createConnectionHandler($connection);

        $this->assertInstanceOf('PHPFastCGI\\FastCGIDaemon\\ConnectionHandler\\ConnectionHandler', $connectionHandler);
    }

    /**
     * Tests that the factory creates a connection handler from a closure
     */
    public function testCreatesConnectionHandlerFromClosure()
    {
        $connectionHandlerFactory = new ConnectionHandlerFactory(function() { });

        $connection = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\Connection\\StreamSocketConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $connectionHandler = $connectionHandlerFactory->createConnectionHandler($connection);

        $this->assertInstanceOf('PHPFastCGI\\FastCGIDaemon\\ConnectionHandler\\ConnectionHandler', $connectionHandler);
    }
}
