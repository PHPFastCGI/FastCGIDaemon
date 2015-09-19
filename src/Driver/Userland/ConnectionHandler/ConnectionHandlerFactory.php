<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * The default implementation of the ConnectionHandlerFactoryInterface.
 */
class ConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(KernelInterface $kernel, ConnectionInterface $connection)
    {
        return new ConnectionHandler($kernel, $connection);
    }
}
