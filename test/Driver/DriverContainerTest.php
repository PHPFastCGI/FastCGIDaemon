<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver;

use PHPFastCGI\FastCGIDaemon\Driver\DriverContainer;

/**
 * Tests the driver container.
 */
class DriverContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the driver container throws an InvalidArgumentException with
     * an unknown driver.
     * 
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDriver()
    {
        $driverContainer = new DriverContainer;

        $driverContainer->getFactory('foo');
    }

    /**
     * Tests that the driver container can create and remember a factory.
     */
    public function testGetFactory()
    {
        $driverContainer = new DriverContainer;

        $userlandDaemonFactory = $driverContainer->getFactory('userland');

        $this->assertInstanceOf('PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemonFactory', $userlandDaemonFactory);

        // Test it doesn't create a new object on future calls
        $this->assertSame($userlandDaemonFactory, $driverContainer->getFactory('userland'));
    }
}
