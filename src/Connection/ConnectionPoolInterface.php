<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

interface ConnectionPoolInterface
{
    /**
     * Accept a connection from the pool
     *
     * @return ConnectionInterface The connection that was accepted
     */
    public function accept();

    /**
     * Closes the connection pool
     */
    public function close();
}
