<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

interface RequestEnvironmentBuilderInterface
{
    /**
     * Add a CGI environment variable parameter.
     * 
     * @param string $name
     * @param string $value
     */
    public function addParam($name, $value);

    /**
     * Add CGI stdin data.
     * 
     * @param string $data
     */
    public function addStdin($data);

    /**
     * Get the request environment given the data that has been added. This
     * method should only be called once per instance. New builders should be
     * instantiated for new requests.
     * 
     * @return RequestEnvironmentInterface
     */
    public function getRequestEnvironment();

    /**
     * Clear all the current data that has been added to the builder. This
     * method will close the stream of any request environment that has
     * been created.
     */
    public function clean();
}
