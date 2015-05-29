<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface KernelInterface
{
    /**
     * Handles a request and returns a response.
     *
     * @param ServerRequestInterface $request
     * 
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request);
}
