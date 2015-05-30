<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;

/**
 * The standard implementation of the DaemonInterface is constructed from a
 * socket stream which is ready to accept connections.
 */
class Daemon implements DaemonInterface
{
    /**
     * @var StreamSocketConnectionPool
     */
    protected $connectionPool;

    /**
     * Constructor.
     * 
     * The stream socket resource must be ready to accept connections.
     *
     * @param resource $stream The stream socket resource
     */
    public function __construct($stream)
    {
        $this->connectionPool = new StreamSocketConnectionPool($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function run(KernelInterface $kernel)
    {
        $this->connectionPool->operate(new ConnectionHandlerFactory($kernel), 5);
    }
}
