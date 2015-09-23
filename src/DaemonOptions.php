<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The default configuration object.
 */
class DaemonOptions
{
    const NO_LIMIT = 0;

    // Possible daemon options
    const LOGGER        = 'logger';
    const REQUEST_LIMIT = 'request-limit';
    const MEMORY_LIMIT  = 'memory-limit';
    const TIME_LIMIT    = 'time-limit';

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * The value of the LOGGER option must implement the PSR-3 LoggerInterface.
     * 
     * For the REQUEST_LIMIT, MEMORY_LIMIT and TIME_LIMIT options, NO_LIMIT can
     * be used to specify that these metrics should not cause the daemon to
     * shutdown.
     *
     * @param array $options The options to configure the daemon with
     * 
     * @throws \InvalidArgumentException On unrecognised option
     */
    public function __construct(array $options = [])
    {
        // Set defaults
        $this->options = [
            self::LOGGER        => new NullLogger(),
            self::REQUEST_LIMIT => self::NO_LIMIT,
            self::MEMORY_LIMIT  => self::NO_LIMIT,
            self::TIME_LIMIT    => self::NO_LIMIT,
        ];

        foreach ($options as $option => $value) {
            if (!isset($this->options[$option])) {
                throw new \InvalidArgumentException('Unknown option: '.$option);
            }

            $this->options[$option] = $value;
        }

        if (!$this->options[self::LOGGER] instanceof LoggerInterface) {
            throw new \InvalidArgumentException('Logger must implement LoggerInterface');
        }
    }

    /**
     * Retrieve the value of one of the daemon options.
     * 
     * @param string $option The option to return
     * 
     * @return mixed The value of the option requested
     * 
     * @throws \InvalidArgumentException On unrecognised option
     */
    public function getOption($option)
    {
        if (!isset($this->options[$option])) {
            throw new \InvalidArgumentException('Unknown option: '.$option);
        }

        return $this->options[$option];
    }
}
