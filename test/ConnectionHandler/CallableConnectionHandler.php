<?php

namespace PHPFastCGI\Test\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerInterface;

/**
 * Implementation of ConnectionHandlerInterface using callbacks.
 */
class CallableConnectionHandler implements ConnectionHandlerInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Constructor.
     * 
     * @param callable            $callback  The callback to use
     * @param ConnectionInterface $connection The connection to handle
     */
    public function __construct($callback, ConnectionInterface $connection)
    {
        $this->callback   = $callback;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        call_user_func_array($this->callback, ['ready', $this->connection]);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        call_user_func_array($this->callback, ['shutdown', $this->connection]);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        call_user_func_array($this->callback, ['close', $this->connection]);
    }
}
