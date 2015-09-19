<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * Objects implementing the ConnectionHandlerFactoryInterface can be used by
 * the ConnectionPool to create handlers for new incoming connections.
 */
interface ConnectionHandlerFactoryInterface
{
    /**
     * Create a connection handler.
     *
     * @param KernelInterface     $kernel     The kernel to use
     * @param ConnectionInterface $connection The connection to handle
     *
     * @return ConnectionHandlerInterface The connection handler
     */
    public function createConnectionHandler(KernelInterface $kernel, ConnectionInterface $connection);
}
