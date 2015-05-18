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
     * @var resource
     */
    protected $body;

    public function __construct(array $server = [], array $query = [], array $post = [], array $files = [], array $cookies = [], $body = null)
    {
        $this->server  = $server;
        $this->query   = $query;
        $this->post    = $post;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->body    = $body;
    }

    public function __destruct()
    {
        if (null !== $this->body) {
            fclose($this->body);
        }
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
