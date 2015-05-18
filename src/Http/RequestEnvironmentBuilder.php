<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

class RequestEnvironmentBuilder implements RequestEnvironmentBuilderInterface
{
    /**
     * @var string[]
     */
    protected $params;

    /**
     * @var resource|null
     */
    protected $stdin;

    public function __construct()
    {
        $this->params = [];
        $this->stdin  = null;
    }

    /**
     * {@inheritdoc}
     */
    public function addParam($name, $value)
    {
        $this->params[strtoupper($name)] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function addStdin($data)
    {
        if (null === $this->stdin) {
            $this->stdin = fopen('php://temp', 'r+');
        }

        fwrite($this->stdin, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestEnvironment()
    {
        if (null !== $this->stdin) {
            rewind($this->stdin);
        }

        $query   = [];
        $post    = [];
        $cookies = [];

        if (isset($this->params['QUERY_STRING'])) {
            parse_str($this->params['QUERY_STRING'], $query);
        }

        if (null !== $this->stdin && isset($this->params['REQUEST_METHOD']) && isset($this->params['CONTENT_TYPE'])) {
            $requestMethod = $this->params['REQUEST_METHOD'];
            $contentType   = $this->params['CONTENT_TYPE'];

            if (strcasecmp($requestMethod, 'POST') === 0 && strcasecmp($contentType, 'application/x-www-form-urlencoded') === 0) {
                $postData = stream_get_contents($this->stdin);
                rewind($this->stdin);

                parse_str($postData, $post);
            }
        }

        if (isset($this->params['HTTP_COOKIE'])) {
            $cookiePairs = explode(';', $this->params['HTTP_COOKIE']);

            foreach ($cookiePairs as $cookiePair) {
                list($name, $value) = explode('=', trim($cookiePair));
                $cookies[$name] = $value;
            }
        }

        $requestEnvironment = new RequestEnvironment($this->params, $query, $post, [], $cookies, $this->stdin);

        return $requestEnvironment;
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->params = [];

        if (null !== $this->stdin) {
            fclose($this->stdin);
            $this->stdin = null;
        }
    }
}
