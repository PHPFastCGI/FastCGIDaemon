<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

/**
 * The RequestBuilderInterface defines a set of methods used by a FastCGIDaemon
 * ConnectionHandler to build PSR-7 server request objects.
 */
interface RequestInterface
{
    /**
     * Get the FastCGI request params.
     *
     * @return array Associative array of FastCGI request params
     */
    public function getParams(): array;

    /**
     * Returns expected contents of $_GET superglobal array.
     */
    public function getQuery(): array;

    /**
     * Returns expected contents of $_POST superglobal array.
     */
    public function getPost(): array;

    /**
     * Returns expected contents of $_COOKIES superglobal.
     */
    public function getCookies(): array;

    /**
     * Get the FastCGI stdin data.
     *
     * @return resource Stream resource containing FastCGI stdin data
     */
    public function getStdin();

    /**
     * Get the request as a PSR-7 server request.
     */
    public function getServerRequest(): ServerRequestInterface;

    /**
     * Get the request as a Symfony HttpFoundation request.
     */
    public function getHttpFoundationRequest(): HttpFoundationRequest;
}
