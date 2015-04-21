<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;

trait StreamSocketExceptionTrait
{
    protected function createExceptionFromLastError($function,
        $errorNumber = false, $errorString = false)
    {
        fclose($this->socket);

        $message = $function . ' failed';

        if (false !== $errorNumber) {
            $message .= ' (' . $errorNumber . ')';
        }

        if (false !== $errorString) {
            $message .= ': ' . $errorString;
        }

        return new ConnectionException($message);
    }
}
