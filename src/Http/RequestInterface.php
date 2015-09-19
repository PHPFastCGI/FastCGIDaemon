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
    public function getParams();

    /**
     * Returns expected contents of $_GET superglobal array.
     *
     * @return array
     */
    public function getQuery();

    /**
     * Returns expected contents of $_POST superglobal array.
     *
     * @return array
     */
    public function getPost();

    /**
     * Returns expected contents of $_COOKIES superglobal.
     *
     * @return array
     */
    public function getCookies();

    /**
     * Get the FastCGI stdin data.
     *
     * @return resource Stream resource containing FastCGI stdin data
     */
    public function getStdin();

    /**
     * Get the request as a PSR-7 server request.
     *
     * @return ServerRequestInterface The request object
     */
    public function getServerRequest();

    /**
     * Get the request as a Symfony HttpFoundation request.
     *
     * @return HttpFoundationRequest The request object
     */
    public function getHttpFoundationRequest();
}
