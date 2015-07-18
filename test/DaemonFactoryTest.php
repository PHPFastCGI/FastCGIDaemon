<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
use PHPFastCGI\FastCGIDaemon\DaemonFactory;

/**
 * Tests the daemon.
 */
class DaemonFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Extract a property from a class using reflection API.
     * 
     * @param object $object
     * @param string $property
     * 
     * @return mixed
     */
    protected function getObjectProperty($object, $property)
    {
        $reflectionProperty = new \ReflectionProperty(get_class($object), $property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }

    /**
     * Tests that the factory can create a daemon from a stream socket.
     */
    public function testCreateDaemonFromStreamSocket()
    {
        $daemonFactory = new DaemonFactory();

        $kernel = new CallbackWrapper(function () {});
        $stream = fopen('php://memory', 'r');

        $daemon = $daemonFactory->createDaemonFromStreamSocket($kernel, $stream);

        // Hack the constructed values out of the daemon object
        $connectionHandlerFactory = $this->getObjectProperty($daemon, 'connectionHandlerFactory');
        $connectionPool           = $this->getObjectProperty($daemon, 'connectionPool');

        // Hack the kernel out of the connection handler factory objecy
        $extractedKernel = $this->getObjectProperty($connectionHandlerFactory, 'kernel');
        $this->assertSame($kernel, $extractedKernel);

        // Hack the server socket out of the connection pool object
        $serverSocket = $this->getObjectProperty($connectionPool, 'serverSocket');
        $this->assertSame($stream, $serverSocket);
    }
}
