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
     * Create a Symfony console application
     *
     * @param KernelInterface|callable $kernel The daemon's kernel
     *
     * @return Application The Symfony console application
     */
    public function createApplication($kernel);

    /**
     * Create a Symfony console command
     *
     * @param KernelInterface|callable $kernel The daemon's kernel
     *
     * @return Command The Symfony console command
     */
    public function createCommand($kernel);
}
