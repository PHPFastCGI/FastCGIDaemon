<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;

class SingleplexedResponderConnectionHandlerFactory implements
    SingleplexedConnectionHandlerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(ConnectionInterface $connection)
    {
        return new SingleplexedResponderConnectionHandler($connection);
    }
}
