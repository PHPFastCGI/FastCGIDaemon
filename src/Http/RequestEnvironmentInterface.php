<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

interface RequestEnvironmentInterface
{
    /**
     * Get the $_SERVER parameters.
     *
     * @return string[]
     */
    public function getServer();

    /**
     * Get the $_GET parameters.
     *
     * @return array
     */
    public function getQuery();

    /**
     * Get the $_POST parameters.
     *
     * @return array
     */
    public function getPost();

    /**
     * Get the $_FILES parameters.
     *
     * @return array
     */
    public function getFiles();

    /**
     * Get the $_COOKIE parameters.
     *
     * @return string[]
     */
    public function getCookies();

    /**
     * Get the body of the message.
     *
     * @return resource|null
     */
    public function getBody();
}
