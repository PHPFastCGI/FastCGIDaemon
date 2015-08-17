<?php

namespace PHPFastCGI\Test\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Implementation of ConnectionHandlerFactoryInterface using callbacks.
 */
class CallableConnectionHandlerFactory implements ConnectionHandlerFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructor.
     * 
     * @param callable $callback The callback to use to handle connections
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Return the current logger
     * 
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionHandler(ConnectionInterface $connection)
    {
        return new CallableConnectionHandler($this->callback, $connection);
    }
}
