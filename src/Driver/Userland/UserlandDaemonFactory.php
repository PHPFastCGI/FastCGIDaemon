<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptionsInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactory;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * A factory class for instantiating UserlandDaemon objects.
 */
class UserlandDaemonFactory
{ 
    /**
     * Create a FastCGI daemon listening on FCGI_LISTENSOCK_FILENO using the
     * userland FastCGI implementation.
     * 
     * @param KernelInterface        $kernel  The kernel to use for the daemon
     * @param DaemonOptionsInterface $options The daemon configuration
     *
     * @return UserlandDaemon
     * 
     * @codeCoverageIgnore The FastCGI daemon
     */
    public function createDaemon(KernelInterface $kernel, DaemonOptionsInterface $options)
    {
        $socket = fopen('php://fd/'.DaemonInterface::FCGI_LISTENSOCK_FILENO, 'r');

        if (false === $socket) {
            throw new \RuntimeException('Could not open FCGI_LISTENSOCK_FILENO');
        }

        return $this->createDaemonFromStreamSocket($kernel, $options, $socket);
    }

    /**
     * Create a FastCGI daemon listening for TCP connections on a given address
     * using the userland FastCGI implementation. The default host is
     * 'localhost'.
     * 
     * @param KernelInterface        $kernel  The kernel to use for the daemon
     * @param DaemonOptionsInterface $options The daemon configuration
     * @param int                    $port    The port to bind to
     * @param string                 $host    The host to bind to
     *
     * @return UserlandDaemon The FastCGI daemon
     * 
     * @codeCoverageIgnore
     */
    public function createTcpDaemon(KernelInterface $kernel, DaemonOptionsInterface $options, $port, $host = 'localhost')
    {
        $address = 'tcp://'.$host.':'.$port;
        $socket  = stream_socket_server($address);

        if (false === $socket) {
            throw new \RuntimeException('Could not create stream socket server on: '.$address);
        }

        return $this->createDaemonFromStreamSocket($kernel, $options, $socket);
    }

    /**
     * Create a FastCGI daemon from a stream socket which is configured for
     * accepting connections using the userland FastCGI implementation.
     *
     * @param KernelInterface        $kernel  The kernel to use for the daemon
     * @param DaemonOptionsInterface $options The daemon configuration
     * @param resource               $socket  The socket to accept connections from
     *
     * @return UserlandDaemon The FastCGI daemon
     */
    public function createDaemonFromStreamSocket(KernelInterface $kernel, DaemonOptionsInterface $options, $socket)
    {
        $connectionPool           = new StreamSocketConnectionPool($socket);
        $connectionHandlerFactory = new ConnectionHandlerFactory;

        return new UserlandDaemon($kernel, $options, $connectionPool, $connectionHandlerFactory);
    }
}
