<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The default implementation of the ConnectionHandlerFactoryInterface.
 */
class ConnectionHandlerFactory implements ConnectionHandlerFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor.
     *
     * @param KernelInterface|callable $kernel The kernel to create handlers for
     * @param LoggerInterface          $logger A logger to use
     */
    public function __construct($kernel, LoggerInterface $logger = null)
    {
        $this->setLogger((null === $logger) ? new NullLogger() : $logger);

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
        return new ConnectionHandler($this->kernel, $connection, $this->logger);
    }
}
