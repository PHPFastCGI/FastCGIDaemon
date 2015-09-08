<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactory;
use PHPFastCGI\FastCGIDaemon\FastCGIExtensionDaemon;

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
        /* if (extension_loaded('fastcgi')) {
            return new FastCGIExtensionDaemon($kernel);
        } */

        // Fallback on raw PHP implementation
        $socket = fopen('php://fd/'.DaemonInterface::FCGI_LISTENSOCK_FILENO, 'r');

        if (false === $socket) {
            throw new \RuntimeException('Could not open FCGI_LISTENSOCK_FILENO');
        }

        return $this->createDaemonFromStreamSocket($kernel, $socket);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function createTcpDaemon($kernel, $port, $host = 'localhost')
    {
        $address = 'tcp://'.$host.':'.$port;
        $socket  = stream_socket_server($address);

        if (false === $socket) {
            throw new \RuntimeException('Could not create stream socket server on: '.$address);
        }

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
