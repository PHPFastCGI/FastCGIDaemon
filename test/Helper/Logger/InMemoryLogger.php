<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Logger;

use Psr\Log\AbstractLogger;

class InMemoryLogger extends AbstractLogger
{
    /**
     * @var array
     */
    private $messages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * Get the logged messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->messages[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }
}
