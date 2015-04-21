<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;

trait SocketExceptionTrait
{
    protected function createExceptionFromLastError($function)
    {
        $errorNumber  = socket_last_error($this->socket);
        $errorMessage = socket_strerror($errorNumber);

        socket_close($this->socket);

        return new ConnectionException($function . ': ' . $errorMessage, $errorNumber);
    }
}
