<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Implementation of ConnectionHandlerInterface using callbacks.
 */
class CallableConnectionPool implements ConnectionPoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructor.
     * 
     * @param callable $callback The callback to use
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
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop)
    {
        call_user_func_array($this->callback, [$connectionHandlerFactory, $timeoutLoop]);
    }
}
