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
     * Create a FastCGI daemon listening on file descriptor.
     *
     * @param KernelInterface $kernel The kernel to use for the daemon
     * @param DaemonOptions|DaemonOptionsInterface $options The daemon configuration
     * @param int $fd file descriptor for listening defaults to FCGI_LISTENSOCK_FILENO
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemon(KernelInterface $kernel, DaemonOptions $options, $fd = DaemonInterface::FCGI_LISTENSOCK_FILENO);

    /**
     * Create a FastCGI daemon listening on a given address. The default host is
     * localhost.
     *
     * @param KernelInterface        $kernel  The kernel to use for the daemon
     * @param DaemonOptionsInterface $options The daemon configuration
     * @param string                 $host    The host to bind to
     * @param int                    $port    The port to bind to
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createTcpDaemon(KernelInterface $kernel, DaemonOptions $options, $host, $port);
}
