<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;

interface ConnectionInterface
{
    /**
     * Read data from the connection
     * 
     * @param int $length Number of bytes to read
     * 
     * @return string Buffer containing the read data
     * 
     * @throws ConnectionException On failure
     */
    public function read($length);

    /**
     * Write data to the connection
     * 
     * @param string $buffer Buffer containing the data to write
     * 
     * @throws ConnectionException On failure
     */
    public function write($buffer);

    /**
     * Tests to see if the connection has been closed
     * 
     * @return bool
     */
    public function isClosed();

    /**
     * Closes the connection it from the pool
     */
    public function close();
}
