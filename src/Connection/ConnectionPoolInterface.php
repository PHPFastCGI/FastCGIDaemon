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
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory The factory used to create connection handlers
     * @param float                             $timeoutLoop              The timeout value to use when waiting for activity on incoming connections
     */
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop);

    /**
     * Shutdown the connection pool cleanly. Usually triggered following a
     * SIGINT.
     */
    public function shutdown();
}
