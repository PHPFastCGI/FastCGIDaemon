<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\DaemonTrait;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactoryInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Exception\UserlandDaemonException;
use PHPFastCGI\FastCGIDaemon\Exception\ShutdownException;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * The standard implementation of the DaemonInterface is constructed from a
 * connection pool and a factory class to generate connection handlers.
 */
class UserlandDaemon implements DaemonInterface
{
    use DaemonTrait;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var DaemonOptions
     */
    private $daemonOptions;

    /**
     * @var ConnectionPoolInterface
     */
    private $connectionPool;

    /**
     * @var ConnectionHandlerFactoryInterface
     */
    private $connectionHandlerFactory;

    /**
     * @var ConnectionHandler[]
     */
    private $connectionHandlers;

    /**
     * Constructor.
     *
     * @param KernelInterface                   $kernel                   The kernel for the daemon to use
     * @param DaemonOptions                     $daemonOptions            The daemon configuration
     * @param ConnectionPoolInterface           $connectionPool           The connection pool to accept connections from
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory A factory class for producing connection handlers
     */
    public function __construct(KernelInterface $kernel, DaemonOptions $daemonOptions, ConnectionPoolInterface $connectionPool, ConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        $this->kernel                   = $kernel;
        $this->daemonOptions            = $daemonOptions;
        $this->connectionPool           = $connectionPool;
        $this->connectionHandlerFactory = $connectionHandlerFactory;

        $this->connectionHandlers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->setupDaemon($this->daemonOptions);

        try {
            while (1) {
                $this->processConnectionPool();

                $this->checkDaemonLimits();
            }
        } catch (ShutdownException $exception) {
            $this->daemonOptions->getOption(DaemonOptions::LOGGER)->notice($exception->getMessage());

            $this->shutdown();
        } catch (\Exception $exception) {
            $this->daemonOptions->getOption(DaemonOptions::LOGGER)->emergency($exception->getMessage());

            $this->connectionPool->close();

            throw $exception;
        }
    }

    /**
     * Wait for connections in the pool to become readable. Create connection
     * handlers for new connections and trigger the ready method when there is
     * data for the handlers to receive. Clean up closed connections.
     */
    private function processConnectionPool()
    {
        $readableConnections = $this->connectionPool->getReadableConnections(5);

        foreach ($readableConnections as $id => $connection) {
            if (!isset($this->connectionHandlers[$id])) {
                $this->connectionHandlers[$id] = $this->connectionHandlerFactory->createConnectionHandler($this->kernel, $connection);
            }

            try {
                $dispatchedRequests = $this->connectionHandlers[$id]->ready();
                $this->incrementRequestCount($dispatchedRequests);
            } catch (UserlandDaemonException $exception) {
                $this->daemonOptions->getOption(DaemonOptions::LOGGER)->error($exception->getMessage());
            }

            if ($this->connectionHandlers[$id]->isClosed()) {
                unset($this->connectionHandlers[$id]);
            }
        }
    }

    /**
     * Gracefully shutdown the daemon.
     */
    private function shutdown()
    {
        $this->connectionPool->shutdown();

        foreach ($this->connectionHandlers as $connectionHandler) {
            $connectionHandler->shutdown();
        }

        while ($this->connectionPool->count() > 0) {
            $this->processConnectionPool();
        }

        $this->connectionPool->close();
    }
}
