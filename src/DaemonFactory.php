<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;

/**
 * A class which can be used to create fully instantiated FastCGI daemons.
 */
class DaemonFactory
{
    /**
     * Create a FastCGI daemon listening on FCGI_LISTENSOCK_FILENO.
     * 
     * @param KernelInterface|callable $kernel The daemon's kernel
     * 
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemon($kernel)
    {
        $socket = fopen('php://fd/' . DaemonInterface::FCGI_LISTENSOCK_FILENO, 'r');

        return $this->createDaemonFromStreamSocket($kernel, $socket);
    }

    /**
     * Create a FastCGI daemon listening on a given address. The default host is
     * localhost.
     *
     * @param KernelInterface|callable $kernel The daemon's kernel
     * @param int                      $port   The port to bind to
     * @param string                   $host   The host to bind to
     * 
     * @return DaemonInterface The FastCGI daemon
     */
    public function createTcpDaemon($kernel, $port, $host = 'localhost')
    {
        $socket = stream_socket_server('tcp://' . $host . ':' . $port);

        return $this->createDaemonFromStreamSocket($kernel, $socket);
    }

    /**
     * Create a FastCGI daemon from a stream socket which is configured for
     * accepting connections.
     * 
     * @param KernelInterface|callable $kernel The daemon's kernel
     * @param resource                 $socket The socket to accept connections from
     * 
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemonFromStreamSocket($kernel, $socket)
    {
        $connectionHandlerFactory = new ConnectionHandlerFactory($kernel);
        $connectionPool           = new StreamSocketConnectionPool($socket);

        return new Daemon($connectionPool, $connectionHandlerFactory);
    }
}
