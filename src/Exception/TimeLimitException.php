<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

/**
 * Thrown when the daemon has exceeded the time limit specified in its
 * configuration.
 */
class TimeLimitException extends ShutdownException
{
}
