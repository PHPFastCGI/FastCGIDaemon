<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Exception\MemoryLimitException;
use PHPFastCGI\FastCGIDaemon\Exception\RequestLimitException;
use PHPFastCGI\FastCGIDaemon\Exception\ShutdownException;
use PHPFastCGI\FastCGIDaemon\Exception\TimeLimitException;

trait DaemonTrait
{
    /**
     * @var int 
     */
    private $requestCount;

    /**
     * @var int
     */
    private $requestLimit;

    /**
     * @var int
     */
    private $memoryLimit;

    /**
     * Loads to configuration from the daemon options and installs signal
     * handlers.
     * 
     * @param DaemonOptionsInterface $daemonOptions
     */
    private function setupDaemon(DaemonOptionsInterface $daemonOptions)
    {
        $this->requestCount = 0;
        $this->requestLimit = $daemonOptions->getRequestLimit();
        $this->memoryLimit  = $daemonOptions->getMemoryLimit();

        $timeLimit = $daemonOptions->getTimeLimit();

        if (DaemonOptionsInterface::NO_LIMIT !== $timeLimit) {
            pcntl_alarm($timeLimit);
        }

        $this->installSignalHandlers();
    }

    /**
     * Increments the request count.
     * 
     * @param int $number The number of requests to increment the count by
     */
    private function incrementRequestCount($number)
    {
        $this->requestCount += $number;
    }

    /**
     * Installs a handler which throws a ShutdownException upon receiving a
     * SIGINT or a SIGALRM.
     * 
     * @throws ShutdownException On receiving a SIGINT or SIGALRM
     */
    private function installSignalHandlers()
    {
        declare(ticks = 1);

        pcntl_signal(SIGINT, function () {
            throw new ShutdownException('Daemon shutdown requested (received SIGINT)');
        });

        pcntl_signal(SIGALRM, function () {
            throw new TimeLimitException('Daemon time limit reached (received SIGALRM)');
        });
    }

    /**
     * Checks the current PHP process against the limits specified in a daemon
     * options object.
     * 
     * @param DaemonOptionsInterface $daemonOptions
     * 
     * @throws ShutdownException When limits in the daemon options are exceeded
     */
    private function checkDaemonLimits()
    {
        if (DaemonOptionsInterface::NO_LIMIT !== $this->requestLimit) {
            if ($this->requestLimit <= $this->requestCount) {
                throw new RequestLimitException('Daemon request limit reached ('.$this->requestCount.' of '.$this->requestLimit.')');
            }
        }

        if (DaemonOptionsInterface::NO_LIMIT !== $this->memoryLimit) {
            $memoryUsage = memory_get_usage(true);

            if ($this->memoryLimit <= $memoryUsage) {
                throw new MemoryLimitException('Daemon memory limit reached ('.$memoryUsage.' of '.$this->memoryLimit.' bytes)');
            }
        }
    }
}
