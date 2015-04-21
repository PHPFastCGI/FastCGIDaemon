<?php

namespace PHPFastCGI\FastCGIDaemon\Exception;

abstract class AbstractRequestException extends ProtocolException
{
    protected $requestId = false;

    /**
     * Get the request id associated with the exception
     * 
     * @return string
     */
    public function getRequestId()
    {
        if (false === $requestId) {
            throw new \LogicException('Request id not set for request exception');
        }

        return $this->requestId;
    }

    /**
     * Set the request id associated with the exception
     * 
     * @param  string                   $requestId The request id
     * @return AbstractRequestException            An instance of the exception
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Returns the protocl status code that the application should respond to
     * if it encounters this exception
     * 
     * @return int
     */
    abstract public function getProtocolStatus();
}
