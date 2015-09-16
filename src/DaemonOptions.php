<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Log\LoggerInterface;

/**
 * The default implementation of the DaemonOptionsInterface.
 */
class DaemonOptions implements DaemonOptionsInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $requestLimit;

    /**
     * @var int
     */
    private $memoryLimit;

    /**
     * @var int
     */
    private $timeLimit;

    /**
     * Constructor.
     * 
     * For the $requestLimit parameter, DaemonOptionsInterface::NO_LIMIT can be
     * used to specify that no number of requests should cause the daemon to
     * shutdown.
     * 
     * For the $memoryLimit parameter, DaemonOptionsInterface::NO_LIMIT can be
     * used to specify that no detected memory usage should cause the daemon
     * to shutdown.
     * 
     * @param LoggerInterface $logger       A logger to use
     * @param int             $requestLimit Number of requests to handle before shutting down
     * @param int             $memoryLimit  Upper bound on amount of memory in bytes allocated to script before shutting down
     * @param int             $timeLimit    Upper bound on the number of seconds to run the daemon for before shutting down
     */
    public function __construct(LoggerInterface $logger, $requestLimit, $memoryLimit, $timeLimit)
    {
        $this->logger       = $logger;
        $this->requestLimit = $requestLimit;
        $this->memoryLimit  = $memoryLimit;
        $this->timeLimit    = $timeLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestLimit()
    {
        return $this->requestLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }
}
