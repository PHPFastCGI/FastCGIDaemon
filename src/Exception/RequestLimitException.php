<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

/**
 * Thrown when the daemon has exceeded the request limit specified in its
 * configuration.
 */
class RequestLimitException extends ShutdownException
{
}
