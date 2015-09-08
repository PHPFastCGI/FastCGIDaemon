<?php

namespace PHPFastCGI\FastCGIDaemon\Command;

use PHPFastCGI\FastCGIDaemon\DaemonFactory;
use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Psr\Log\LoggerAwareInterface;
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
     * @var DaemonFactoryInterface
     */
    private $daemonFactory;

    /**
     * @var KernelInterface|callable
     */
    private $kernel;

    /**
     * Constructor.
     *
     * @param KernelInterface|callable $kernel        The kernel to be given to the daemon
     * @param DaemonFactoryInterface   $daemonFactory The factory to use to create the daemon
     * @param string                   $name          The name of the daemon run command
     * @param string                   $description   The description of the daemon run command
     */
    public function __construct($kernel, DaemonFactoryInterface $daemonFactory = null, $name = null, $description = null)
    {
        $daemonFactory = $daemonFactory ?: new DaemonFactory;
        $name          = $name          ?: self::DEFAULT_NAME;
        $description   = $description   ?: self::DEFAULT_DESCRIPTION;

        parent::__construct($name);

        $this
            ->setDescription($description)
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'TCP port to listen on (if not present, daemon will listen on FCGI_LISTENSOCK_FILENO)')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'TCP host to listen on');

        $this->daemonFactory = $daemonFactory;

        if (!$kernel instanceof KernelInterface && !is_callable($kernel)) {
            throw new \InvalidArgumentException('Kernel parameter must be an instance of KernelInterface or a callable');
        }

        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = $input->getOption('port');
        $host = $input->getOption('host');

        if (null !== $port) {
            // If we have the port, create a TCP daemon

            if (null !== $host) {
                $daemon = $this->daemonFactory->createTcpDaemon($this->kernel, $port, $host);
            } else {
                $daemon = $this->daemonFactory->createTcpDaemon($this->kernel, $port);
            }
        } elseif (null !== $host) {
            // If we have the host but not the port, we cant create a TCP daemon - throw exception
            throw new \InvalidArgumentException('TCP port option must be set if host option is set');
        } else {
            // With no host or port, listen on FCGI_LISTENSOCK_FILENO (default)
            $daemon = $this->daemonFactory->createDaemon($this->kernel);
        }

        if ($daemon instanceof LoggerAwareInterface) {
            $daemon->setLogger(new ConsoleLogger($output));
        }

        $daemon->run();
    }
}
