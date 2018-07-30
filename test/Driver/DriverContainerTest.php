<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver;

use PHPFastCGI\FastCGIDaemon\Driver\DriverContainer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the driver container.
 */
class DriverContainerTest extends TestCase
{
    /**
     * Tests that the driver container throws an InvalidArgumentException with
     * an unknown driver.
     */
    public function testInvalidDriver()
    {
        $driverContainer = new DriverContainer();

        $this->expectException(\InvalidArgumentException::class);
        $driverContainer->getFactory('foo');
    }

    /**
     * Tests that the driver container can create and remember a factory.
     */
    public function testGetFactory()
    {
        $driverContainer = new DriverContainer();

        $userlandDaemonFactory = $driverContainer->getFactory('userland');

        $this->assertInstanceOf('PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemonFactory', $userlandDaemonFactory);

        // Test it doesn't create a new object on future calls
        $this->assertSame($userlandDaemonFactory, $driverContainer->getFactory('userland'));
    }
}
