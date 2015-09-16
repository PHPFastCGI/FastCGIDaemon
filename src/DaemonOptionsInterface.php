<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Log\LoggerInterface;

/**
 * The DaemonOptionsInterface contains the configuration options to control the
 * behaviour of a daemon.
 */
interface DaemonOptionsInterface
{
    const NO_LIMIT = 0;

    /**
     * Get a logger to use.
     * 
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * Get request limit. A value of 0 (DaemonOptionsInterface::NO_LIMIT)
     * represents that there is no number of requests that should trigger the
     * daemon to shutdown.
     * 
     * @return int
     */
    public function getRequestLimit();

    /**
     * Get the memory limit in bytes. A value of 0
     * (DaemonOptionsInterface::NO_LIMIT) represents that there is no amount of
     * memory allocated to the daemon instance that should trigger a shutdown.
     * 
     * @return int
     */
    public function getMemoryLimit();

    /**
     * Get the time limit in seconds. A value of 0
     * (DaemonOptionsInterface::NO_LIMIT) represents that there is no time
     * limit on the execution of the daemon instance that should trigger a
     * shutdown.
     * 
     * @return int
     */
    public function getTimeLimit();
}
