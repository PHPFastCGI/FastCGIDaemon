<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;

/**
 * The default implementation of the DaemonFactoryInterface.
 */
class DaemonFactory implements DaemonFactoryInterface
{
    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function createDaemon($kernel)
    {
        $socket = fopen('php://fd/' . DaemonInterface::FCGI_LISTENSOCK_FILENO, 'r');

        return $this->createDaemonFromStreamSocket($kernel, $socket);
    }

    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function createTcpDaemon($kernel, $port, $host = 'localhost')
    {
        $socket = stream_socket_server('tcp://' . $host . ':' . $port);

        return $this->createDaemonFromStreamSocket($kernel, $socket);
    }

    /**
     * {@inheritdoc}
     */
    public function createDaemonFromStreamSocket($kernel, $socket)
    {
        $connectionHandlerFactory = new ConnectionHandlerFactory($kernel);
        $connectionPool           = new StreamSocketConnectionPool($socket);

        return new Daemon($connectionPool, $connectionHandlerFactory);
    }
}
