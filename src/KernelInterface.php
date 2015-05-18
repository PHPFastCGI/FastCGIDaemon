<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestEnvironmentInterface;
use PHPFastCGI\FastCGIDaemon\Http\ResponseInterface;

interface KernelInterface
{
    /**
     * Handles a request and returns a response.
     * 
     * @return ResponseInterface
     */
    public function handleRequest(RequestEnvironmentInterface $requestEnvironment);
}
