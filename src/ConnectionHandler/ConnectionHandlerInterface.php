<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Http\Request;

interface ConnectionHandlerInterface
{
    /**
     * Send a response to a request
     * 
     * @param Request $request  The request object that is being responded to
     * @param string  $response The HTTP response message
     */
    public function sendResponse(Request $request, $response);
}
