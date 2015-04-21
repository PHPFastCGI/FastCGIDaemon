<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class MultiplexingUnsupportedException extends AbstractRequestException
{
    public function getProtocolStatus()
    {
        return DaemonInterface::FCGI_CANT_MPX_CONN;
    }   
}
