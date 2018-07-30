<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemonFactory;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the userland daemon factory.
 */
class UserlandDaemonFactoryTest extends TestCase
{
    /**
     * Tests that the factory can create a daemon from a stream socket.
     */
    public function testCreateDaemonFromStreamSocket()
    {
        $daemonFactory = new UserlandDaemonFactory();

        $kernel  = new MockKernel();
        $options = new DaemonOptions();
        $socket  = stream_socket_server('tcp://localhost:7000');

        $daemon = $daemonFactory->createDaemonFromStreamSocket($kernel, $options, $socket);

        // Hack the constructed values out of the daemon object
        $connectionPool = $this->getObjectProperty($daemon, 'connectionPool');

        // Hack the kernel out of the daemon
        $extractedKernel = $this->getObjectProperty($daemon, 'kernel');
        $this->assertSame($kernel, $extractedKernel);

        // Hack the options out of the daemon
        $extractedOptions = $this->getObjectProperty($daemon, 'daemonOptions');
        $this->assertSame($options, $extractedOptions);

        // Hack the server socket out of the connection pool object
        $extractedSocket = $this->getObjectProperty($connectionPool, 'serverSocket');
        $this->assertSame($socket, $extractedSocket);

        fclose($socket);
    }

    /**
     * Extract a property from a class using reflection API.
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    private function getObjectProperty($object, $property)
    {
        $reflectionProperty = new \ReflectionProperty(get_class($object), $property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
