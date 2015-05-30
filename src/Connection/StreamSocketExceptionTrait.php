<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;

/**
 * The StreamSocketExceptionTrait helps to convert PHP errors and warnings into
 * exceptions that can be handled.
 */
trait StreamSocketExceptionTrait
{
    /**
     * Creates a formatted exception from the last error that occurecd.
     * 
     * @param string $function    The function that failed
     * @param int    $errorNumber The error number given
     * @param string $errorString The error message given
     * 
     * @return ConnectionException
     */
    protected function createExceptionFromLastError($function, $errorNumber = false, $errorString = false)
    {
        fclose($this->socket);

        $message = $function.' failed';

        if (false !== $errorNumber) {
            $message .= ' ('.$errorNumber.')';
        }

        if (false !== $errorString) {
            $message .= ': '.$errorString;
        }

        return new ConnectionException($message);
    }
}
