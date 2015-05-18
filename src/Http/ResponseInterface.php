<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

interface ResponseInterface
{
    /**
     * Returns the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Returns the HTTP reason phrase.
     *
     * @return string
     */
    public function getReasonPhrase();

    /**
     * Returns the HTTP lines.
     *
     * @return string[]
     */
    public function getHeaderLines();

    /**
     * Get message body.
     *
     * @return resource|string|null
     */
    public function getBody();
}
