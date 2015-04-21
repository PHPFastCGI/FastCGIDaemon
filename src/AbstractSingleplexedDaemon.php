<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\SingleplexedConnectionHandlerFactoryInterface;

abstract class AbstractSingleplexedDaemon implements DaemonInterface
{
    private $connectionPool;
    private $connectionHandlerFactory;

    public function __construct(ConnectionPoolInterface $connectionPool,
        SingleplexedConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        $this->connectionPool           = $connectionPool;
        $this->connectionHandlerFactory = $connectionHandlerFactory;
    }

    public function getRequest($returnOnError = false)
    {
        do {
            $connection = $this->connectionPool->accept();

            $connectionHandler = $this->connectionHandlerFactory
                ->createConnectionHandler($connection);

            $request = $connectionHandler->getRequest();
        } while ((false === $returnOnError) && (null === $request));

        return $request;
    }
}
