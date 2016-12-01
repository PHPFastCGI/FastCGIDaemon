<?php

namespace PHPFastCGI\FastCGIDaemon\Command;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\Driver\DriverContainerInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

class DaemonRunCommand extends Command
{
    const DEFAULT_NAME        = 'run';
    const DEFAULT_DESCRIPTION = 'Run the FastCGI daemon';

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var DriverContainerInterface
     */
    private $driverContainer;

    /**
     * @var DaemonInterface
     */
    private $daemon;

    /**
     * Constructor.
     *
     * @param KernelInterface          $kernel          The kernel to be given to the daemon
     * @param DriverContainerInterface $driverContainer The driver container
     * @param string                   $name            The name of the daemon run command
     * @param string                   $description     The description of the daemon run command
     */
    public function __construct(KernelInterface $kernel, DriverContainerInterface $driverContainer, $name = null, $description = null)
    {
        $this->kernel          = $kernel;
        $this->driverContainer = $driverContainer;
        $this->daemon          = null;

        $name        = $name        ?: self::DEFAULT_NAME;
        $description = $description ?: self::DEFAULT_DESCRIPTION;

        parent::__construct($name);

        $this
            ->setDescription($description)
            ->addOption('port',          null, InputOption::VALUE_OPTIONAL, 'TCP port to listen on (if not present, daemon will listen on FCGI_LISTENSOCK_FILENO)')
            ->addOption('host',          null, InputOption::VALUE_OPTIONAL, 'TCP host to listen on')
            ->addOption('fd',            null, InputOption::VALUE_OPTIONAL, 'File descriptor to listen on - defaults to FCGI_LISTENSOCK_FILENO', DaemonInterface::FCGI_LISTENSOCK_FILENO)
            ->addOption('request-limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of requests to handle before shutting down')
            ->addOption('memory-limit',  null, InputOption::VALUE_OPTIONAL, 'The memory limit on the daemon instance before shutting down')
            ->addOption('time-limit',    null, InputOption::VALUE_OPTIONAL, 'The time limit on the daemon in seconds before shutting down')
            ->addOption('driver',        null, InputOption::VALUE_OPTIONAL, 'The implementation of the FastCGI protocol to use', 'userland');
    }

    /**
     * Creates a daemon configuration object from the Symfony command input and
     * output objects.
     *
     * @param InputInterface  $input  The  Symfony command input
     * @param OutputInterface $output The Symfony command output
     *
     * @return DaemonOptions The daemon configuration
     */
    private function getDaemonOptions(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $requestLimit = $input->getOption('request-limit') ?: DaemonOptions::NO_LIMIT;
        $memoryLimit  = $input->getOption('memory-limit')  ?: DaemonOptions::NO_LIMIT;
        $timeLimit    = $input->getOption('time-limit')    ?: DaemonOptions::NO_LIMIT;

        return new DaemonOptions([
            DaemonOptions::LOGGER        => $logger,
            DaemonOptions::REQUEST_LIMIT => $requestLimit,
            DaemonOptions::MEMORY_LIMIT  => $memoryLimit,
            DaemonOptions::TIME_LIMIT    => $timeLimit,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = $input->getOption('port');
        $host = $input->getOption('host');
        $fd = $input->getOption('fd');

        $daemonOptions = $this->getDaemonOptions($input, $output);

        $driver        = $input->getOption('driver');
        $daemonFactory = $this->driverContainer->getFactory($driver);

        if (null !== $port) {
            // If we have the port, create a TCP daemon
            $this->daemon = $daemonFactory->createTcpDaemon($this->kernel, $daemonOptions, $host ?: 'localhost', $port);
        } elseif (null !== $host) {
            // If we have the host but not the port, we cant create a TCP daemon - throw exception
            throw new \InvalidArgumentException('TCP port option must be set if host option is set');
        } else {
            // With no host or port, listen on FCGI_LISTENSOCK_FILENO (default)
            $this->daemon = $daemonFactory->createDaemon($this->kernel, $daemonOptions, $fd);
        }

        $this->daemon->run();
    }

    /**
     * Flag the daemon for shutdown.
     */
    public function flagShutdown()
    {
        if (null === $this->daemon) {
            throw new \RuntimeException('There is no daemon running');
        }

        $this->daemon->flagShutdown();
    }
}
