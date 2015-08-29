<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * The FastCGIDaemon uses Objects that implement the KernelInterface to respond
 * to HTTP requests.
 */
interface KernelInterface
{
    /**
     * Handles a request and returns a response.
     *
     * @param RequestInterface $request FastCGI HTTP request object
     *
     * @return ResponseInterface|HttpFoundationResponse HTTP response message
     */
    public function handleRequest(RequestInterface $request);
}
