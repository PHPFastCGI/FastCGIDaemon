<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;

/**
 * Objects implementing the ConnectionHandlerFactoryInterface can be used by
 * the ConnectionPool to create handlers for new incoming connections.
 */
interface ConnectionHandlerFactoryInterface
{
    /**
     * Create a connection handler.
     *
     * @param ConnectionInterface $connection The connection to handle
     *
     * @return ConnectionHandlerInterface The connection handler
     */
    public function createConnectionHandler(ConnectionInterface $connection);
}
