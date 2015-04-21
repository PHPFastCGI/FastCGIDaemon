<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Http\Request;

interface SingleplexedConnectionHandlerInterface extends
    ConnectionHandlerInterface
{
    /**
     * Read the whole request from the connection before returning it as a
     * request object for processing
     * 
     * @return Request|null The request object or null
     */
    public function getRequest();
}
