<?php

namespace PHPFastCGI\FastCGIDaemon;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Objects that implement the ApplicationFactoryInterface can be used to create
 * Symfony console commands and applications.
 */
interface ApplicationFactoryInterface
{
    /**
     * Create a Symfony console application.
     *
     * @param KernelInterface|callable $kernel             The kernel to use
     * @param string                   $commandName        The name of the daemon run command
     * @param string                   $commandDescription The description of the daemon run command
     *
     * @return Application The Symfony console application
     */
    public function createApplication($kernel, $commandName = null, $commandDescription = null);

    /**
     * Create a Symfony console command.
     *
     * @param KernelInterface|callable $kernel             The kernel to use
     * @param string                   $commandName        The name of the daemon run command
     * @param string                   $commandDescription The description of the daemon run command
     *
     * @return Command The Symfony console command
     */
    public function createCommand($kernel, $commandName = null, $commandDescription = null);
}
