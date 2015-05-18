<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

class RequestEnvironment implements RequestEnvironmentInterface
{
    /**
     * @var string[]
     */
    protected $server;

    /**
     * @var array
     */
    protected $query;

    /**
     * @var array
     */
    protected $post;

     /**
     * @var array
     */
    protected $files;

    /**
     * @var string[]
     */
    protected $cookies;

    /**
     * @var resource|null
     */
    protected $body;

    /**
     * Constructor.
     * 
     * @param string[]      $server
     * @param array         $query
     * @param array         $post
     * @param array         $files
     * @param string[]      $cookies
     * @param resource|null $body
     */
    public function __construct(array $server = [], array $query = [], array $post = [], array $files = [], array $cookies = [], $body = null)
    {
        $this->server  = $server;
        $this->query   = $query;
        $this->post    = $post;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->body    = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }
}
