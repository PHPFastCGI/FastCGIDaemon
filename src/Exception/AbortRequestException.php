<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class AbortRequestException extends AbstractRequestException
{
    public function getProtocolStatus()
    {
        return DaemonInterface::FCGI_REQUEST_COMPLETE;
    }   
}
