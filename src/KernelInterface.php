<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Objects that implement the KernelInterface can be used by the FastCGIDaemon
 * to respond to PSR-7 HTTP server request messages.
 */
interface KernelInterface
{
    /**
     * Handles a request and returns a response.
     *
     * @param ServerRequestInterface $request The PSR-7 HTTP server request message
     *
     * @return ResponseInterface The PSR-7 HTTP server response message
     */
    public function handleRequest(ServerRequestInterface $request);
}
