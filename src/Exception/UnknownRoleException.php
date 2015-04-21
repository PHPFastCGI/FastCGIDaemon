<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class UnknownRoleException extends AbstractRequestException
{
    public function getProtocolStatus()
    {
        return DaemonInterface::FCGI_UNKNOWN_ROLE;
    }   
}
