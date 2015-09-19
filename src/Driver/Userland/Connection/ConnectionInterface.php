<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\ConnectionException;

/**
 * The connection interface defines a set of methods that abstract operations
 * on incoming connections from the method by which they were accepted.
 */
interface ConnectionInterface
{
    /**
     * Read data from the connection.
     *
     * @param int $length Number of bytes to read
     *
     * @return string Buffer containing the read data
     *
     * @throws ConnectionException On failure
     */
    public function read($length);

    /**
     * Write data to the connection.
     *
     * @param string $buffer Buffer containing the data to write
     *
     * @throws ConnectionException On failure
     */
    public function write($buffer);

    /**
     * Tests to see if the connection has been closed.
     *
     * @return bool True if the connection has been closed, false otherwise
     */
    public function isClosed();

    /**
     * Closes the connection it from the pool.
     */
    public function close();
}
