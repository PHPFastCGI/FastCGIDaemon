<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class ConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(ConnectionInterface $connection)
    {
        return new ConnectionHandler($this->kernel, $connection);
    }
}
