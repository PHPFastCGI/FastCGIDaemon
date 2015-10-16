<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\UserlandDaemonException;
use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Client\ConnectionWrapper;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Logger\InMemoryLogger;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockKernel;
use Zend\Diactoros\Response;

/**
 * Tests the daemon.
 */
abstract class AbstractDaemonTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the daemon shuts down after reaching its request .
     */
    public function testRequestLimit()
    {
        // Set a memory limit to make sure that it isn't breached (added 10MB on top of peak usage)
        $context = $this->createTestingContext(1, memory_get_peak_usage() + (10 * 1024 * 1024));

        $socket            = stream_socket_client($context['address']);
        $connectionWrapper = new ConnectionWrapper($socket);

        $connectionWrapper->writeRequest(1, [], '');

        $context['daemon']->run();

        fclose($socket);

        $this->assertEquals('Daemon request limit reached (1 of 1)', $context['logger']->getMessages()[0]['message']);
    }

    /**
     * Tests that the daemon shuts down after reaching its memory limit.
     */
    public function testMemoryLimit()
    {
        $context = $this->createTestingContext(DaemonOptions::NO_LIMIT, 1);

        $socket            = stream_socket_client($context['address']);
        $connectionWrapper = new ConnectionWrapper($socket);

        $connectionWrapper->writeRequest(1, [], '');

        $context['daemon']->run();

        fclose($socket);

        $this->assertContains('Daemon memory limit reached', $context['logger']->getMessages()[0]['message']);
    }

    /**
     * Tests that the daemon shuts down after reaching its time limit.
     */
    public function testTimeLimit()
    {
        $context = $this->createTestingContext(DaemonOptions::NO_LIMIT, DaemonOptions::NO_LIMIT, 1);

        $context['daemon']->run();

        $this->assertContains('Daemon time limit reached', $context['logger']->getMessages()[0]['message']);
    }

    /**
     * Tests that the daemon shuts down after reaching its time limit.
     */
    public function testException()
    {
        $context = $this->createTestingContext();

        $socket            = stream_socket_client($context['address']);
        $connectionWrapper = new ConnectionWrapper($socket);

        $connectionWrapper->writeRequest(1, ['EXCEPTION' => 'boo'], '');

        try {
            $context['daemon']->run();
        } catch (\Exception $exception) {
            $this->assertEquals('boo', $exception->getMessage());
        }

        $this->assertContains('boo', $context['logger']->getMessages()[0]['message']);
    }

    /**
     * Tests that the daemon shuts down after receiving a SIGINT.
     */
    public function testShutdown()
    {
        $context = $this->createTestingContext();

        $socket            = stream_socket_client($context['address']);
        $connectionWrapper = new ConnectionWrapper($socket);

        $connectionWrapper->writeRequest(1, ['SHUTDOWN' => ''], '');

        $context['daemon']->run();

        $this->assertEquals('Daemon shutdown requested (received SIGINT)', $context['logger']->getMessages()[0]['message']);
    }

    /**
     * Create a testing context to use
     * 
     * @param string $requestLimit
     * @param string $memoryLimit
     * @param string $timeLimit
     * 
     * @return DaemonInterface
     */
    protected function createTestingContext($requestLimit = DaemonOptions::NO_LIMIT, $memoryLimit = DaemonOptions::NO_LIMIT, $timeLimit = DaemonOptions::NO_LIMIT)
    {
        $context = [
            'kernel' => new MockKernel([
                'handleRequest' => function (RequestInterface $request) {
                    $params = $request->getParams();

                    if (isset($params['EXCEPTION'])) {
                        throw new \Exception($params['EXCEPTION']);
                    } elseif (isset($params['DAEMON_EXCEPTION'])) {
                        throw new UserlandDaemonException($params['DAEMON_EXCEPTION']);
                    } elseif (isset($params['SHUTDOWN'])) {
                        posix_kill(posix_getpid(), SIGINT);
                    }

                    return new Response();
                },
            ]),
            'logger'  => new InMemoryLogger(),
            'address' => 'tcp://localhost:7000'
        ];

        $context['options'] = new DaemonOptions([
            DaemonOptions::LOGGER        => $context['logger'],
            DaemonOptions::REQUEST_LIMIT => $requestLimit,
            DaemonOptions::MEMORY_LIMIT  => $memoryLimit,
            DaemonOptions::TIME_LIMIT    => $timeLimit,
        ]);

        $context['daemon'] = $this->createDaemon($context);

        return $context;
    }

    /**
     * Create daemon object to be tested from the context array.
     * 
     * The context array contains the following keys:
     *  - kernel (KernelInterface)
     *  - logger (LoggerInterface)
     *  - options (DaemonOptions)
     *  - address (string, e.g. tcp://localhost:7000)
     * 
     * @param array $context
     * 
     * @return DaemonInterface
     */
    abstract protected function createDaemon(array $context);
}
