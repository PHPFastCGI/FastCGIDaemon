<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Command\DaemonRunCommand;
use PHPFastCGI\FastCGIDaemon\DaemonFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Tests the daemon run command
 */
class DaemonRunCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests an \InvalidArgumentException is thrown when it isn't constructed
     * with a callable.
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        new DaemonRunCommand('name', 'description', new DaemonFactory(), 'not a callable function');
    }

    /**
     * Tests that two optional options 'port' and 'host' are added to the
     * command.
     */
    public function testOptions()
    {
        $command = new DaemonRunCommand('name', 'description', new DaemonFactory(), function () { });

        $definition = $command->getDefinition();

        $portOption = $definition->getOption('port');
        $this->assertTrue($portOption->isValueOptional());

        $hostOption = $definition->getOption('host');
        $this->assertTrue($hostOption->isValueOptional());
    }

    /**
     * Test invalid option configuration. An Invalid argument exception should
     * be thrown if the host option is supplied without the port optional also.
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOptions()
    {
        $command = new DaemonRunCommand('name', 'description', new DaemonFactory(), function () { });

        $input  = new ArrayInput(['--host' => '_']);
        $output = new NullOutput();

        $command->run($input, $output);
    }

    /**
     * Create a mock daemon to use for testing.
     */
    private function createMockDaemon()
    {
        $mockDaemon = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\Daemon')
            ->disableOriginalConstructor()
            ->setMethods(['setLogger', 'run'])
            ->getMock();

        $mockDaemon->expects($this->once())->method('setLogger');
        $mockDaemon->expects($this->once())->method('run');

        return $mockDaemon;
    }

    /**
     * Create a mock daemon to use for testing
     */
    private function createMockDaemonFactory($createMethod)
    {
        $mockDaemonFactory = $this
            ->getMockBuilder('PHPFastCGI\\FastCGIDaemon\\DaemonFactory')
            ->disableOriginalConstructor()
            ->setMethods([$createMethod])
            ->getMock();

        return $mockDaemonFactory;
    }

    /**
     * Test the creation of the default daemon.
     */
    public function testCreateDefaultDaemon()
    {
        $input  = new ArrayInput([]);
        $output = new NullOutput();

        $mockDaemon        = $this->createMockDaemon();
        $mockDaemonFactory = $this->createMockDaemonFactory('createDaemon');

        $mockDaemonFactory
            ->expects($this->once())
            ->method('createDaemon')
            ->will($this->returnValue($mockDaemon));

        $command = new DaemonRunCommand('name', 'description', $mockDaemonFactory, function () { });

        $command->run($input, $output);
    }

    /**
     * Test the creation of the default daemon given the port and the host.
     */
    public function testCreateTcpDaemonWithHost()
    {
        $host = 'localhost';
        $port = 5000;

        $input  = new ArrayInput(['--host' => $host, '--port' => $port]);
        $output = new NullOutput();

        $mockDaemon        = $this->createMockDaemon();
        $mockDaemonFactory = $this->createMockDaemonFactory('createTcpDaemon');

        $kernel = function () { };

        $mockDaemonFactory
            ->expects($this->once())
            ->method('createTcpDaemon')
            ->with($this->equalTo($kernel), $this->equalTo($port), $this->equalTo($host))
            ->will($this->returnValue($mockDaemon));

        $command = new DaemonRunCommand('name', 'description', $mockDaemonFactory, $kernel);

        $command->run($input, $output);
    }

    /**
     * Test the creation of the TCP daemon given only the port.
     */
    public function testCreateTcpDaemonWithoutHost()
    {
        $port = 5000;

        $input  = new ArrayInput(['--port' => $port]);
        $output = new NullOutput();

        $mockDaemon        = $this->createMockDaemon();
        $mockDaemonFactory = $this->createMockDaemonFactory('createTcpDaemon');

        $kernel = function () { };

        $mockDaemonFactory
            ->expects($this->once())
            ->method('createTcpDaemon')
            ->with($this->equalTo($kernel), $this->equalTo($port), $this->equalTo('localhost'))
            ->will($this->returnValue($mockDaemon));

        $command = new DaemonRunCommand('name', 'description', $mockDaemonFactory, $kernel);

        $command->run($input, $output);
    }
}
