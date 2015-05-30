<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * The default implementation of the ConnectionHandlerFactoryInterface.
 */
class ConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel The kernel to create handlers for
     */
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
