<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerInterface;

class Request
{
    protected $connectionHandler;
    protected $requestId;
    protected $server;
    protected $content;

    public function __construct(ConnectionHandlerInterface $connectionHandler,
        $requestId, array $server, $content)
    {
        $this->connectionHandler = $connectionHandler;
        $this->requestId         = $requestId;
        $this->server            = $server;
        $this->content           = $content;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function respond($response)
    {
        $this->connectionHandler->sendResponse($this, $response);
    }
}
