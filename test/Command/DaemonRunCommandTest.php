<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Command\DaemonRunCommand;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\MockDriverContainer;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockDaemon;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockDaemonFactory;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Tests the daemon run command.
 */
class DaemonRunCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the command options are configured properly.
     */
    public function testConfiguration()
    {
        $command = new DaemonRunCommand(new MockKernel, new MockDriverContainer);

        $definition = $command->getDefinition();

        $optionalOptions = ['port', 'host', 'request-limit', 'memory-limit', 'time-limit', 'driver'];

        foreach ($optionalOptions as $optionName) {
            $this->assertTrue($definition->getOption($optionName)->isValueOptional());
        }

        $defaults = ['driver' => 'userland'];

        foreach ($defaults as $optionName => $defaultValue) {
            $this->assertEquals($defaultValue, $definition->getOption($optionName)->getDefault());
        }
    }

    /**
     * Test with no options set (default). The command should use ::createDaemon
     * method on the factory rather than ::createTcpDaemon.
     */
    public function testNoOptions()
    {
        $expectations = ['driver' => 'userland'];

        $context = $this->createTestingContext($expectations);

        $context['command']->run(new ArrayInput([]), new NullOutput);

        $this->assertEquals('run',          $context['daemon']->getDelegatedCalls()[0][0]);
        $this->assertEquals('createDaemon', $context['daemonFactory']->getDelegatedCalls()[0][0]);
        $this->assertEquals('getFactory',   $context['driverContainer']->getDelegatedCalls()[0][0]);
    }

    /**
     * Test that the daemon options object is correctly configured
     */
    public function testDaemonOptions()
    {
        $requestLimit = 500;
        $memoryLimit  = 600;
        $timeLimit    = 700;

        $input = new ArrayInput([
            '--request-limit' => $requestLimit,
            '--memory-limit'  => $memoryLimit,
            '--time-limit'    => $timeLimit,
        ]);
        $output = new NullOutput;

        $logger  = new ConsoleLogger($output);
        $options = new DaemonOptions($logger, $requestLimit, $memoryLimit, $timeLimit);

        $expectations = ['options' => $options, 'driver' => 'userland'];

        $context = $this->createTestingContext($expectations);

        $context['command']->run($input, $output);

        $this->assertEquals('run',          $context['daemon']->getDelegatedCalls()[0][0]);
        $this->assertEquals('createDaemon', $context['daemonFactory']->getDelegatedCalls()[0][0]);
        $this->assertEquals('getFactory',   $context['driverContainer']->getDelegatedCalls()[0][0]);
    }

    /**
     * Test with only the host option set. An Invalid argument exception should
     * be thrown if the host option is supplied without the port optional also.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testHostOptionOnly()
    {
        $context = $this->createTestingContext([]);

        $input = new ArrayInput(['--host' => 'foo']);

        $context['command']->run($input, new NullOutput);
    }

    /**
     * Test with only the port option set.
     */
    public function testPortOptionOnly()
    {
        $expectations = [
            'port'   => 500,
            'host'   => 'localhost',
            'driver' => 'userland',
        ];

        $context = $this->createTestingContext($expectations);

        $input = new ArrayInput(['--port' => $expectations['port']]);

        $context['command']->run($input, new NullOutput);

        $this->assertEquals('run',             $context['daemon']->getDelegatedCalls()[0][0]);
        $this->assertEquals('createTcpDaemon', $context['daemonFactory']->getDelegatedCalls()[0][0]);
        $this->assertEquals('getFactory',      $context['driverContainer']->getDelegatedCalls()[0][0]);
    }

    /**
     * Construct mock objects set to test for expected values of different
     * parameters when specified.
     * 
     * @param array $expectations
     * 
     * @return array An associative array containing the constructed objects.
     */
    private function createTestingContext(array $expectations)
    {
        $assertExpected = function ($property, $value) use ($expectations) {
            if (isset($expectations[$property])) {
                $this->assertEquals($expectations[$property], $value);
            }
        };

        $mockKernel = new MockKernel;
        $mockDaemon = new MockDaemon(['run' => false]);

        $mockDaemonFactory = new MockDaemonFactory([
            'createTcpDaemon' => function ($kernel, $options, $host, $port) use ($assertExpected, $mockKernel, $mockDaemon) {
                $this->assertEquals($mockKernel, $kernel);

                $assertExpected('port',    $port);
                $assertExpected('host',    $host);
                $assertExpected('options', $options);

                return $mockDaemon;
            },
            'createDaemon' => function ($kernel, $options) use ($assertExpected, $mockKernel, $mockDaemon) {
                $this->assertEquals($mockKernel, $kernel);

                $assertExpected('options', $options);

                return $mockDaemon;
            },
        ]);

        $mockDriverContainer = new MockDriverContainer([
            'getFactory' => function ($driver) use ($assertExpected, $mockDaemonFactory) {
                $assertExpected('driver', $driver);

                return $mockDaemonFactory;
            }
        ]);

        return [
            'command'         => new DaemonRunCommand($mockKernel, $mockDriverContainer),
            'daemon'          => $mockDaemon,
            'daemonFactory'   => $mockDaemonFactory,
            'driverContainer' => $mockDriverContainer,
        ];
    }
}
