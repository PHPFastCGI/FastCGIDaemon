<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon;

interface DaemonOptionsInterface
{
    const NO_LIMIT = 0;

    // Possible daemon options
    const LOGGER        = 'logger';
    const REQUEST_LIMIT = 'request-limit';
    const MEMORY_LIMIT  = 'memory-limit';
    const TIME_LIMIT    = 'time-limit';
    const AUTO_SHUTDOWN = 'auto-shutdown';

    /**
     * Retrieve the value of one of the daemon options.
     *
     * @param string $option The option to return
     *
     * @return mixed The value of the option requested
     *
     * @throws \InvalidArgumentException On unrecognised option
     */
    public function getOption(string $option);
}
