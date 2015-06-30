<?php

namespace PHPFastCGI\FastCGIDaemon\Command;

use PHPFastCGI\FastCGIDaemon\DaemonFactory;
use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

class DaemonRunCommand extends Command
{
    /**
     * @var KernelInterface|callable
     */
    protected $kernel;

    /**
     * Constructor.
     * 
     * @param string                   $name        The name of the daemon run command
     * @param string                   $description The description of the daemon run command
     * @param KernelInterface|callable $kernel      The kernel to be given to the daemon
     */
    public function __construct($name, $description, $kernel)
    {
        parent::__construct($name);

        $this
            ->setDescription($description)
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'TCP port to listen on (if not present, daemon will listen on FCGI_LISTENSOCK_FILENO)')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'TCP host to listen on');

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
        $daemonFactory = new DaemonFactory();

        if ($input->hasOption('port')) {
            // If we have the port, create a TCP daemon
            $port = $input->getOption('port');

            if ($input->hasOption('host')) {
                $host  = $input->getOption('host');
                $daemon = $daemonFactory->createTcpDaemon($kernel, $port, $host);
            } else {
                $daemon = $daemonFactory->createTcpDaemon($kernel, $port);
            }
        } elseif ($input->hasOption('host')) {
            // If we have the host but not the port, we cant create a TCP daemon - throw exception
            throw new \InvalidArgumentException('TCP port option must be set if host option is set');
        } else {
            // With no host or port, listen on FCGI_LISTENSOCK_FILENO (default)
            $daemon = $daemonFactory->createDaemon($this->kernel);
        }

        $daemon->setLogger(new ConsoleLogger($output));

        $daemon->run();
    }
}
