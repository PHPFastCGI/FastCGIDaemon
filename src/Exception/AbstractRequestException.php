<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

abstract class AbstractRequestException extends ProtocolException
{
    /**
     * Returns the protocl status code that the application should respond to
     * if it encounters this exception
     * 
     * @return int
     */
    abstract public function getProtocolStatus();
}
