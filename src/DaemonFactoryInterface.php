<?php

namespace PHPFastCGI\FastCGIDaemon;

/**
 * Objects that implement the DaemonFactoryInterface can be used to create
 * FastCGI daemons that listen on FCGI_LISTENSOCK_FILENO, a configured stream
 * socket resource or a TCP host and port.
 */
interface DaemonFactoryInterface
{
    /**
     * Create a FastCGI daemon listening on FCGI_LISTENSOCK_FILENO.
     *
     * @param KernelInterface|callable $kernel The daemon's kernel
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemon($kernel);

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
    public function createTcpDaemon($kernel, $port, $host = 'localhost');

    /**
     * Create a FastCGI daemon from a stream socket which is configured for
     * accepting connections.
     *
     * @param KernelInterface|callable $kernel The daemon's kernel
     * @param resource                 $socket The socket to accept connections from
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemonFromStreamSocket($kernel, $socket);
}
