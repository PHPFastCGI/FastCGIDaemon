<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Command\DaemonRunCommand;
use PHPFastCGI\FastCGIDaemon\Driver\DriverContainer;
use PHPFastCGI\FastCGIDaemon\Driver\DriverContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * The default implementation of the ApplicationFactoryInterface.
 */
final class ApplicationFactory implements ApplicationFactoryInterface
{
    /**
     * @var DriverContainerInterface
     */
    private $driverContainer;

    /**
     * Constructor.
     *
     * @param DriverContainerInterface $driverContainer The driver container
     */
    public function __construct(DriverContainerInterface $driverContainer = null)
    {
        $this->driverContainer = $driverContainer ?: new DriverContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication($kernel, string $commandName = null, string $commandDescription = null): Application
    {
        $command = $this->createCommand($kernel, $commandName, $commandDescription);

        $application = new Application();
        $application->add($command);

        return $application;
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($kernel, string $commandName = null, string $commandDescription = null): Command
    {
        $kernelObject = $this->getKernelObject($kernel);

        return new DaemonRunCommand($kernelObject, $this->driverContainer, $commandName, $commandDescription);
    }

    /**
     * Converts the kernel parameter to an object implementing the KernelInterface
     * if it is a callable.
     *
     * Otherwise returns the object directly.
     *
     * @param KernelInterface|callable $kernel The kernel
     *
     * @return KernelInterface The kernel as an object implementing the KernelInterface
     */
    private function getKernelObject($kernel): KernelInterface
    {
        if ($kernel instanceof KernelInterface) {
            return $kernel;
        } elseif (is_callable($kernel)) {
            return new CallbackKernel($kernel);
        }

        throw new \InvalidArgumentException('Kernel must be callable or an instance of KernelInterface');
    }
}
