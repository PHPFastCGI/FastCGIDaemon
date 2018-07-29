<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

/**
 * The default implementation of the RequestInterface.
 */
class Request implements RequestInterface
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var resource
     */
    private $stdin;

    /**
     * Constructor.
     *
     * @param array    $params The FastCGI server params as an associative array
     * @param resource $stdin  The FastCGI stdin data as a stream resource
     */
    public function __construct(array $params, $stdin)
    {
        $this->params = [];

        foreach ($params as $name => $value) {
            $this->params[strtoupper($name)] = $value;
        }

        $this->stdin  = $stdin;

        rewind($this->stdin);
    }

    /**
     * {@inheritdoc}
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        $query = null;

        if (isset($this->params['QUERY_STRING'])) {
            parse_str($this->params['QUERY_STRING'], $query);
        }

        return $query ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPost()
    {
        $post = null;

        if (isset($this->params['REQUEST_METHOD']) && isset($this->params['CONTENT_TYPE'])) {
            $requestMethod = $this->params['REQUEST_METHOD'];
            $contentType   = $this->params['CONTENT_TYPE'];

            if (strcasecmp($requestMethod, 'POST') === 0 && stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
                $postData = stream_get_contents($this->stdin);
                rewind($this->stdin);

                parse_str($postData, $post);
            }
        }

        return $post ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCookies()
    {
        $cookies = [];

        if (isset($this->params['HTTP_COOKIE'])) {
            $cookiePairs = explode(';', $this->params['HTTP_COOKIE']);

            foreach ($cookiePairs as $cookiePair) {
                list($name, $value) = explode('=', trim($cookiePair));
                $cookies[$name] = $value;
            }
        }

        return $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerRequest()
    {
        if (!class_exists(ServerRequest::class)) {
            throw new \RuntimeException('You need to install zendframework/zend-diactoros^1.8 to use PSR-7 requests.');
        }

        $query   = $this->getQuery();
        $post    = $this->getPost();
        $cookies = $this->getCookies();

        $server  = ServerRequestFactory::normalizeServer($this->params);
        $headers = ServerRequestFactory::marshalHeaders($server);
        $uri     = ServerRequestFactory::marshalUriFromServer($server, $headers);
        $method  = ServerRequestFactory::get('REQUEST_METHOD', $server, 'GET');

        $request = new ServerRequest($server, [], $uri, $method, $this->stdin, $headers);

        return $request
            ->withCookieParams($cookies)
            ->withQueryParams($query)
            ->withParsedBody($post);
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpFoundationRequest()
    {
        if (!class_exists(HttpFoundationRequest::class)) {
            throw new \RuntimeException('You need to install symfony/http-foundation:^4.0 to use HttpFoundation requests.');
        }

        $query   = $this->getQuery();
        $post    = $this->getPost();
        $cookies = $this->getCookies();

        return new HttpFoundationRequest($query, $post, [], $cookies, [], $this->params, $this->stdin);
    }
}
