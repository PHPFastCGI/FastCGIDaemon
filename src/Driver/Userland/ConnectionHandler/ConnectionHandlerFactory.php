<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * The default implementation of the ConnectionHandlerFactoryInterface.
 */
final class ConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(KernelInterface $kernel, ConnectionInterface $connection): ConnectionHandlerInterface
    {
        return new ConnectionHandler($kernel, $connection);
    }
}
