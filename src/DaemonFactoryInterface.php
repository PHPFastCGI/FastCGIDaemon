<?php

declare(strict_types=1);

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
     * @param DaemonOptionsInterface $options The daemon configuration
     * @param int $fd file descriptor for listening defaults to FCGI_LISTENSOCK_FILENO
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemon(KernelInterface $kernel, DaemonOptions $options, int $fd = DaemonInterface::FCGI_LISTENSOCK_FILENO): DaemonInterface;

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
    public function createTcpDaemon(KernelInterface $kernel, DaemonOptions $options, string $host, int $port): DaemonInterface;

    /**
     * Create a FastCGI daemon from a stream socket which is configured for
     * accepting connections using the userland FastCGI implementation.
     *
     * @param KernelInterface $kernel  The kernel to use for the daemon
     * @param DaemonOptions   $options The daemon configuration
     * @param resource        $socket  The socket to accept connections from
     *
     * @return DaemonInterface The FastCGI daemon
     */
    public function createDaemonFromStreamSocket(KernelInterface $kernel, DaemonOptions $options, int $socket): DaemonInterface;
}
