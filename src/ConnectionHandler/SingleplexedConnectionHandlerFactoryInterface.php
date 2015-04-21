<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;

interface SingleplexedConnectionHandlerFactoryInterface
{
    /**
     * Create a connection handler
     * 
     * @param ConnectionInterface $connection The connection to handle
     * 
     * @return SingleplexedConnectionHandlerInterface The connection handler
     */
    public function createConnectionHandler(ConnectionInterface $connection);
}
