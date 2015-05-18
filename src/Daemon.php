<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;

class Daemon implements DaemonInterface
{
    /**
     * @var StreamSocketConnectionPool
     */
    protected $connectionPool;

    /**
     * Constructor.
     *
     * @param resource $stream
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
