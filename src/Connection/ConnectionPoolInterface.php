<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;

/**
 * Objects implementing the ConnectionPoolInterface pass incoming connections
 * off to ConnectionHandler instances that have been created from a connection
 * handler factory.
 */
interface ConnectionPoolInterface
{
    /**
     * Uses the connection handler factory to instantiate connection handlers
     * when new connections are made to the connection pool. Monitors current
     * connections and triggers them when read operations will not block.
     *
     * This method only returns if the pool is unable to accept future
     * connections.
     *
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory The factory used to create connection handlers
     * @param float                             $timeoutLoop              The timeout value to use when waiting for activity on incoming connections
     */
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop);
}
