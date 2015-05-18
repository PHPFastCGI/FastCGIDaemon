<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;

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
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory
     * @param float                             $timeoutLoop
     *
     * @return ConnectionInterface The connection that was accepted
     */
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop);
}
