<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection;

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
     *
     * @throws \RuntimeException On encountering fatal error
     */
    public function getReadableConnections(int $timeout): array;

    /**
     * Returns the number of active connections in the pool.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Stop the connection pool from accepting new connections.
     */
    public function shutdown(): void;

    /**
     * Close the connection pool and free any associated resources.
     */
    public function close(): void;
}
