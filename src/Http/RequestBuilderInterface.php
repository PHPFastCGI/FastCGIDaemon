<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The RequestBuilderInterface defines a set of methods used by a FastCGIDaemon
 * ConnectionHandler to build PSR-7 server request objects.
 */
interface RequestBuilderInterface
{
    /**
     * Add a CGI environment variable parameter.
     *
     * @param string $name  The name of the parameter
     * @param string $value The value of the parameter
     */
    public function addParam($name, $value);

    /**
     * Add CGI stdin data.
     *
     * @param string $data The data
     */
    public function addStdin($data);

    /**
     * Get the request given the data that has been added. This method should
     * only be called once per instance. New builders should be instantiated for
     * new requests.
     *
     * @return ServerRequestInterface The request object for the builder
     */
    public function getRequest();

    /**
     * Clear all the current data that has been added to the builder. This
     * method will close the stream of any request environment that has
     * been created.
     */
    public function clean();
}
