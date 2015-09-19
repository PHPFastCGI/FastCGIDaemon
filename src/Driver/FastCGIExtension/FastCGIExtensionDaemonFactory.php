<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\FastCGIExtension;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptionsInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

/**
 * The implementation of DaemonFactoryInterface for the FastCGI extension daemon.
 */
class FastCGIExtensionDaemonFactory implements DaemonFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createDaemon(KernelInterface $kernel, DaemonOptionsInterface $options)
    {
        return new FastCGIExtensionDaemon($kernel, $options, new \FastCGIApplication());
    }

    /**
     * {@inheritdoc}
     */
    public function createTcpDaemon(KernelInterface $kernel, DaemonOptionsInterface $options, $host, $port)
    {
        if (!in_array($host, ['localhost', '127.0.0.1'])) {
            throw new \InvalidArgumentException('This driver can only bind to localhost');
        }

        $fastCGIApplication = new \FastCGIApplication(':'.$port);
        return new FastCGIExtensionDaemon($kernel, $options, $fastCGIApplication);
    }
}
