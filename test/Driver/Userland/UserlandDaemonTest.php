<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactory;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemon;
use PHPFastCGI\Test\FastCGIDaemon\Driver\AbstractDaemonTestCase;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Client\ConnectionWrapper;

/**
 * Tests the daemon.
 */
class UserlandDaemonTest extends AbstractDaemonTestCase
{
    /**
     * Tests that the daemon cleanly handles internal exceptions (such as
     * protocol and connection exceptions).
     */
    public function testDaemonException()
    {
        $context = $this->createTestingContext();

        $socket1            = stream_socket_client($context['address']);
        $connectionWrapper1 = new ConnectionWrapper($socket1);
        $connectionWrapper1->writeRequest(1, ['DAEMON_EXCEPTION' => 'boo'], '');

        $socket2            = stream_socket_client($context['address']);
        $connectionWrapper2 = new ConnectionWrapper($socket2);
        $connectionWrapper2->writeRequest(2, ['SHUTDOWN' => true], '');

        $context['daemon']->run();

        $this->assertContains('boo',  $context['logger']->getMessages()[0]['message']);
    }

    protected function createDaemon(array $context)
    {
        $serverSocket             = stream_socket_server($context['address']);
        $connectionPool           = new StreamSocketConnectionPool($serverSocket);
        $connectionHandlerFactory = new ConnectionHandlerFactory;

        return new UserlandDaemon($context['kernel'], $context['options'], $connectionPool, $connectionHandlerFactory);
    }
}
