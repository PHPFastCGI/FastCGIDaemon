<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

/**
 * Thrown when the daemon has exceeded the memory limit specified in its
 * configuration.
 */
class MemoryLimitException extends ShutdownException
{
}
