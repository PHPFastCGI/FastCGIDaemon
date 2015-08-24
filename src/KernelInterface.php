<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * Objects that implement the KernelInterface can be used by the FastCGIDaemon
 * to 
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
