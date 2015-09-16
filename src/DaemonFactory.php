<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemonFactory;

/**
 * The default implementation of the DaemonFactoryInterface.
 */
class DaemonFactory implements DaemonFactoryInterface
{
    /**
     * @var UserlandDaemonFactory
     */
    private $userlandDaemonFactory;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userlandDaemonFactory = new UserlandDaemonFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function createDaemon(KernelInterface $kernel, DaemonOptionsInterface $options)
    {
        return $this->userlandDaemonFactory->createDaemon($kernel, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function createTcpDaemon(KernelInterface $kernel, DaemonOptionsInterface $options, $port, $host = 'localhost')
    {
        return $this->userlandDaemonFactory->createTcpDaemon($kernel, $options, $port, $host);
    }

    /**
     * {@inheritdoc}
     */
    public function createDaemonFromStreamSocket(KernelInterface $kernel, DaemonOptionsInterface $options, $socket)
    {
        return $this->userlandDaemonFactory->createDaemonFromStreamSocket($kernel, $options, $socket);
    }
}
