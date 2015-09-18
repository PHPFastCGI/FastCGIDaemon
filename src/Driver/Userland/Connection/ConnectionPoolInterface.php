<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;

/**
 * Objects implementing the ConnectionPoolInterface pass incoming connections
 * off to ConnectionHandler instances that have been created from a connection
 * handler factory.
 */
interface ConnectionPoolInterface
{
    /**
     * Blocking method that waits for connections in the pool to become
     * readable. New connections are accepted automatically.
     * 
     * This method returns an array of ConnectionInterface objects that are
     * readable. This array can be empty.
     *
     * @param int $timeout Upper bound on the amount of time to wair for readable connections
     * 
     * @return ConnectionInterface[]
     */
    public function getReadableConnections($timeout);

    /**
     * Returns the number of active connections in the pool.
     * 
     * @return int
     */
    public function count();

    /**
     * Stop the connection pool from accepting new connections.
     */
    public function shutdown();

    /**
     * Close the connection pool and free any associated resources.
     */
    public function close();
}
