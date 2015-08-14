<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Command\DaemonRunCommand;
use Symfony\Component\Console\Application;
/**
 * The default implementation of the ApplicationFactoryInterface.
 */
class ApplicationFactory implements ApplicationFactoryInterface
{
    /**
     * @var DaemonFactoryInterface
     */
    protected $daemonFactory;

    /**
     * Constructor.
     * 
     * @param DaemonFactoryInterface $daemonFactory The factory to use to create daemons
     */
    public function __construct(DaemonFactoryInterface $daemonFactory = null)
    {
        $this->daemonFactory = $daemonFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication($kernel, $commandName = null, $commandDescription = null)
    {
        $command = $this->createCommand($kernel, $commandName, $commandDescription);

        $application = new Application;
        $application->add($command);

        return $application;
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($kernel, $commandName = null, $commandDescription = null)
    {
        return new DaemonRunCommand($kernel, $this->daemonFactory, $commandName, $commandDescription);
    }
}
