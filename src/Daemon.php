<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;

/**
 * The standard implementation of the DaemonInterface is constructed from a
 * connection pool and a factory class to generate connection handlers.
 */
class Daemon implements DaemonInterface
{
    /**
     * @var ConnectionPoolInterface
     */
    protected $connectionPool;

    /**
     * @var ConnectionHandlerFactoryInterface
     */
    protected $connectionHandlerFactory;

    /**
     * Constructor.
     * 
     * @param ConnectionPoolInterface           $connectionPool           The connection pool to accept connections from
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory A factory class for producing connection handlers
     */
    public function __construct(ConnectionPoolInterface $connectionPool, ConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        $this->connectionPool           = $connectionPool;
        $this->connectionHandlerFactory = $connectionHandlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->connectionPool->operate($this->connectionHandlerFactory, 5);
    }
}
