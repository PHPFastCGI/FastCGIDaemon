<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Exception\ShutdownException;

trait DaemonTrait
{
    /**
     * @var bool
     */
    private $isShutdown = false;

    /**
     * @var string
     */
    private $shutdownMessage = '';

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
     * @var bool
     */
    private $autoShutdown;

    /**
     * Flags the daemon for shutting down.
     *
     * @param string $message Optional shutdown message
     */
    public function flagShutdown(string $message = null): void
    {
        $this->isShutdown = true;
        $this->shutdownMessage = (null === $message ? 'Daemon flagged for shutdown' : $message);
    }

    /**
     * Loads to configuration from the daemon options and installs signal
     * handlers.
     *
     * @param DaemonOptionsInterface $daemonOptions
     */
    private function setupDaemon(DaemonOptionsInterface $daemonOptions): void
    {
        $this->requestCount = 0;
        $this->requestLimit = (int) $daemonOptions->getOption(DaemonOptions::REQUEST_LIMIT);
        $this->memoryLimit  = (int) $daemonOptions->getOption(DaemonOptions::MEMORY_LIMIT);
        $this->autoShutdown = (bool) $daemonOptions->getOption(DaemonOptions::AUTO_SHUTDOWN);

        $timeLimit = (int) $daemonOptions->getOption(DaemonOptions::TIME_LIMIT);

        if (DaemonOptions::NO_LIMIT !== $timeLimit) {
            pcntl_alarm($timeLimit);
        }

        $this->installSignalHandlers();
    }

    /**
     * Increments the request count and looks for application errors.
     *
     * @param int[] $statusCodes The status codes of sent responses
     */
    private function considerStatusCodes(array $statusCodes): void
    {
        $this->requestCount += count($statusCodes);

        if ($this->autoShutdown) {
            foreach ($statusCodes as $statusCode) {
                if ($statusCode >= 500 && $statusCode < 600) {
                    $this->flagShutdown('Automatic shutdown following status code: ' . $statusCode);
                    break;
                }
            }
        }
    }

    /**
     * Installs a handler which throws a ShutdownException upon receiving a
     * SIGINT or a SIGALRM.
     *
     * @throws ShutdownException On receiving a SIGINT or SIGALRM
     */
    private function installSignalHandlers(): void
    {
        declare (ticks = 1);

        pcntl_signal(SIGINT, function () {
            throw new ShutdownException('Daemon shutdown requested (received SIGINT)');
        });

        pcntl_signal(SIGALRM, function () {
            throw new ShutdownException('Daemon time limit reached (received SIGALRM)');
        });
    }

    /**
     * Checks the current PHP process against the limits specified in a daemon
     * options object. This function will also throw an exception if the daemon
     * has been flagged for shutdown.
     *
     * @throws ShutdownException When limits in the daemon options are exceeded
     */
    private function checkDaemonLimits(): void
    {
        if ($this->isShutdown) {
            throw new ShutdownException($this->shutdownMessage);
        }

        pcntl_signal_dispatch();

        if (DaemonOptions::NO_LIMIT !== $this->requestLimit) {
            if ($this->requestLimit <= $this->requestCount) {
                throw new ShutdownException('Daemon request limit reached ('.$this->requestCount.' of '.$this->requestLimit.')');
            }
        }

        if (DaemonOptions::NO_LIMIT !== $this->memoryLimit) {
            $memoryUsage = memory_get_usage(true);

            if ($this->memoryLimit <= $memoryUsage) {
                throw new ShutdownException('Daemon memory limit reached ('.$memoryUsage.' of '.$this->memoryLimit.' bytes)');
            }
        }
    }
}
