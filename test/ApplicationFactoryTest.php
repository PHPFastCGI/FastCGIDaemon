<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\ApplicationFactory;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;

/**
 * Tests the application factory.
 */
class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the factory can create a Symfony console application with a
     * callable kernel.
     */
    public function testCreateApplicationWithCallable()
    {
        $applicationFactory = new ApplicationFactory();

        $name        = 'foo';
        $description = 'bar';

        $application = $applicationFactory->createApplication(function () { }, $name, $description);

        $this->assertInstanceOf('Symfony\\Component\\Console\\Application', $application);
        $this->assertTrue($application->has($name));
        $this->assertEquals($description, $application->get($name)->getDescription());
    }

    /**
     * Tests that the factory can create a Symfony console application with a
     * KernelInterface.
     */
    public function testCreateApplication()
    {
        $applicationFactory = new ApplicationFactory();

        $application = $applicationFactory->createApplication(new MockKernel());

        $this->assertInstanceOf('Symfony\\Component\\Console\\Application', $application);
    }

    /**
     * Tests that an invalid kernel throws an InvalidArgumentException.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidKernel()
    {
        (new ApplicationFactory())->createApplication('foo');
    }
}
