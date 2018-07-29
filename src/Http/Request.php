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
     * @var int
     */
    private static $bufferSize = 10485760; // 10 MB

    /**
     * @var string
     */
    private static $uploadDir = null;

    /**
     * @var array
     */
    private $uploadedFiles = [];

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
    public function cleanUploadedFiles()
    {
        foreach ($this->uploadedFiles as $file) {
            @unlink($file['tmp_name']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function setBufferSize($size)
    {
        static::$bufferSize = $size;
    }

    /**
     * {@inheritdoc}
     */
    public static function getBufferSize()
    {
        return static::$bufferSize;
    }

    /**
     * {@inheritdoc}
     */
    public static function setUploadDir($dir)
    {
        static::$uploadDir = $dir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getUploadDir()
    {
        return static::$uploadDir ?: sys_get_temp_dir();
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

            if (strcasecmp($requestMethod, 'POST') === 0 && stripos($contentType, 'multipart/form-data') === 0) {
                if (preg_match('/boundary=(.*)?/', $contentType, $matches)) {
                    list($postData, $this->uploadedFiles) = $this->parseMultipartFormData($this->stdin, $matches[1]);
                    parse_str($postData, $post);

                    return $post;
                }
            }

            if (strcasecmp($requestMethod, 'POST') === 0 && stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
                $postData = stream_get_contents($this->stdin);
                rewind($this->stdin);

                parse_str($postData, $post);
            }
        }

        return $post ?: [];
    }

    private function parseMultipartFormData($stream, $boundary) {
        $post = "";
        $files = [];
        $fieldType = $fieldName = $filename = $mimeType = null;
        $inHeader = $getContent = false;

        while (!feof($stream)) {
            $getContent = $fieldName && !$inHeader;
            $buffer = stream_get_line($stream, static::$bufferSize, "\r\n" . ($getContent ? '--'.$boundary : ''));

            if ($inHeader && strlen($buffer) == 0) {
                $inHeader = false;
            } else {
                if ($getContent) {
                    if ($fieldType === 'data') {
                        $post .= (isset($post[0]) ? '&' : '') . $fieldName . "=" . urlencode($buffer);
                    } elseif ($fieldType === 'file' && $filename) {
                        $tmp_path = $this->getUploadDir().'/'.substr(md5(rand().time()), 0, 16);
                        $err = file_put_contents($tmp_path, $buffer);
                        $files[$fieldName] = [
                            'type' => $mimeType ?: 'application/octet-stream',
                            'name' => $filename,
                            'tmp_name' => $tmp_path,
                            'error' => ($err === false) ? true : 0,
                            'size' => filesize($tmp_path),
                        ];
                        $filename = $mimeType = null;
                    }
                    $fieldName = $fieldType = null;
                } elseif (strpos($buffer, 'Content-Disposition') === 0) {
                    $inHeader = true;
                    if (preg_match('/name=\"([^\"]*)\"/', $buffer, $matches)) {
                        $fieldName = $matches[1];
                    }
                    if ($isFile = preg_match('/filename=\"([^\"]*)\"/', $buffer, $matches)) {
                        $filename = $matches[1];
                    }
                    $fieldType = $isFile ? 'file' : 'data';
                } elseif (strpos($buffer, 'Content-Type') === 0) {
                    $inHeader = true;
                    if (preg_match('/Content-Type: (.*)?/', $buffer, $matches)) {
                        $mimeType = trim($matches[1]);
                    }
                }
            }
        }

        return [$post, $files];
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

        $request = new ServerRequest($server, $this->uploadedFiles, $uri, $method, $this->stdin, $headers);

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

        return new HttpFoundationRequest($query, $post, [], $cookies, $this->uploadedFiles, $this->params, $this->stdin);
    }
}
