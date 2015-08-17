<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The standard implementation of the DaemonInterface is constructed from a
 * connection pool and a factory class to generate connection handlers.
 */
class Daemon implements DaemonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * @param LoggerInterface                   $logger                   A logger to use
     */
    public function __construct(ConnectionPoolInterface $connectionPool, ConnectionHandlerFactoryInterface $connectionHandlerFactory, LoggerInterface $logger = null)
    {
        $this->connectionPool           = $connectionPool;
        $this->connectionHandlerFactory = $connectionHandlerFactory;

        $this->setLogger((null === $logger) ? new NullLogger() : $logger);

        if ($this->connectionPool instanceof LoggerAwareInterface) {
            $this->connectionPool->setLogger($this->logger);
        }

        if ($this->connectionHandlerFactory instanceof LoggerAwareInterface) {
            $this->connectionHandlerFactory->setLogger($this->logger);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        try {
            while (1) {
                $this->connectionPool->operate($this->connectionHandlerFactory, 5);
                // @codeCoverageIgnoreStart
            }
            // @codeCoverageIgnoreEnd
        } catch (\RuntimeException $exception) {
            $this->logger->emergency($exception->getMessage());
            throw $exception;
        }

        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd
}
