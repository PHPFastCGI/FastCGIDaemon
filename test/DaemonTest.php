<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Daemon;
use PHPFastCGI\Test\FastCGIDaemon\Connection\CallableConnectionPool;
use PHPFastCGI\Test\FastCGIDaemon\ConnectionHandler\CallableConnectionHandlerFactory;
use PHPFastCGI\Test\FastCGIDaemon\Logger\InMemoryLogger;

/**
 * Tests the daemon.
 */
class DaemonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the daemon runs a connection pool with a handler factory.
     */
    public function testRun()
    {
        $logger = new InMemoryLogger;

        // Create mock connection handler factory
        $mockConnectionHandlerFactory = new CallableConnectionHandlerFactory(function () { });

        // Create mock connection pool
        $connectionPoolCallback = function ($connectionHandlerFactory, $timeout) use ($mockConnectionHandlerFactory) {
            $this->assertSame($mockConnectionHandlerFactory, $connectionHandlerFactory);
            $this->assertEquals(5, $timeout);

            throw new \RuntimeException('foo');
        };
        $mockConnectionPool = new CallableConnectionPool($connectionPoolCallback);

        // Create daemon
        $daemon = new Daemon($mockConnectionPool, $mockConnectionHandlerFactory, $logger);

        // Test loggers have been set
        $this->assertSame($logger, $mockConnectionHandlerFactory->getLogger());

        try {
            $daemon->run();
            $this->fail('Should not be reached');
        } catch (\RuntimeException $exception) {
            $this->assertEquals('foo', $exception->getMessage());

            $messages = $logger->getMessages();
            $this->assertEquals('emergency', $messages[0]['level']);
            $this->assertEquals('foo',       $messages[0]['message']);
        }
    }

    /**
     * Tests that the daemon shuts down cleanly after a SIGINT
     */
    public function testInterruptSignal()
    {
        $logger = new InMemoryLogger;

        // Create mock connection handler factory
        $mockConnectionHandlerFactory = new CallableConnectionHandlerFactory(function () { });

        // Create mock connection pool that sends a SIGINT to itself immediately
        $connectionPoolCallback = function () {
            posix_kill(posix_getpid(), SIGINT);
        };
        $mockConnectionPool = new CallableConnectionPool($connectionPoolCallback);

        // Create daemon
        $daemon = new Daemon($mockConnectionPool, $mockConnectionHandlerFactory, $logger);

        $daemon->run();

        // Check that the daemon exits and the correct message is logged
        $messages = $logger->getMessages();
        $this->assertEquals('notice',                            $messages[0]['level']);
        $this->assertEquals('Received SIGINT, shutting down...', $messages[0]['message']);
    }
}
