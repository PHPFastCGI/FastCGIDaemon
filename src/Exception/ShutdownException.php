<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

/**
 * Shutdown exceptions are thrown to trigger a graceful shutdown of the daemon.
 *
 * They may be triggered by a SIGINT or by exceeding limits specified in the
 * daemon configuration (such as memory and request limits).
 */
class ShutdownException extends \RuntimeException
{
}
