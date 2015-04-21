<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

interface ConnectionInterface
{
    /**
     * Read data from the connection
     * 
     * @param  int    $length Number of bytes to read
     * @return string         Buffer containing the read data
     */
    public function read($length);

    /**
     * Write data to the connection
     * 
     * @param string $buffer Buffer containing the data to write
     */
    public function write($buffer);

    /**
     * Closes the connection
     */
    public function close();
}
