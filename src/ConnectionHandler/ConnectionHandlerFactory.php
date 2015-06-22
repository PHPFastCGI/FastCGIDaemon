<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
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
     * @param KernelInterface|callable $kernel The kernel to create handlers for
     */
    public function __construct($kernel)
    {
        if ($kernel instanceof KernelInterface) {
            $this->kernel = $kernel;
        } else {
            $this->kernel = new CallbackWrapper($kernel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(ConnectionInterface $connection)
    {
        return new ConnectionHandler($this->kernel, $connection);
    }
}
