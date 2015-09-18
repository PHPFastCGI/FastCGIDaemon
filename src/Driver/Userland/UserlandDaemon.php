<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptionsInterface;
use PHPFastCGI\FastCGIDaemon\DaemonTrait;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactoryInterface;
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
     * @var DaemonOptionsInterface
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
     * @param DaemonOptionsInterface            $daemonOptions            The daemon configuration
     * @param ConnectionPoolInterface           $connectionPool           The connection pool to accept connections from
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory A factory class for producing connection handlers
     */
    public function __construct(KernelInterface $kernel, DaemonOptionsInterface $daemonOptions, ConnectionPoolInterface $connectionPool, ConnectionHandlerFactoryInterface $connectionHandlerFactory)
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
            $this->daemonOptions->getLogger()->notice($exception->getMessage());

            $this->shutdown();
        } catch (\RuntimeException $exception) {
            $this->daemonOptions->getLogger()->emergency($exception->getMessage());

            $this->connectionPool->close();

            throw $exception;
        }
    }

    private function processConnectionPool()
    {
        $readableConnections = $this->connectionPool->getReadableConnections(5);

        foreach ($readableConnections as $id => $connection) {
            if (!isset($this->connectionHandlers[$id])) {
                $this->connectionHandlers[$id] = $this->connectionHandlerFactory->createConnectionHandler($this->kernel, $connection);
            }
 
            $dispatchedRequests = $this->connectionHandlers[$id]->ready();
            $this->incrementRequestCount($dispatchedRequests);

            if ($this->connectionHandlers[$id]->isClosed()) {
                unset($this->connectionHandlers[$id]);
            }
        }
    }

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
