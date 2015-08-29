<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;

/**
 * Implementation of ConnectionHandlerInterface using callbacks.
 */
class CallableConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var callable|null
     */
    private $shutdownCallback;

    /**
     * Constructor.
     * 
     * @param callable $callback         The callback to use
     * @param callable $shutdownCallback The shutdown callback to use
     */
    public function __construct($callback, $shutdownCallback = null)
    {
        $this->callback         = $callback;
        $this->shutdownCallback = $shutdownCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop)
    {
        call_user_func_array($this->callback, [$connectionHandlerFactory, $timeoutLoop]);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        if (null !== $this->shutdownCallback) {
            call_user_func($this->shutdownCallback);
        }
    }
}
