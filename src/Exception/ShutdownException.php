<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

/**
 * Thrown AFTER the daemon has been shutdown cleanly. This is used to cleanly
 * distinguish between other faults that could occur during operation.
 */
class ShutdownException extends \RuntimeException
{
}
