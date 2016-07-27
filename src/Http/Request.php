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
    protected static $buffer_size = 10 * 1024 * 1024; // 10 MB

    /**
     * @var string
     */
    protected static $upload_dir = null;

    /**
     * @var array
     */
    protected $uploaded_files = [];

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
        foreach ($this->uploaded_files as $file) {
            @unlink($file['tmp_name']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function setBufferSize($size)
    {
        static::$buffer_size = $size;
    }

    /**
     * {@inheritdoc}
     */
    public static function getBufferSize()
    {
        return static::$buffer_size;
    }

    /**
     * {@inheritdoc}
     */
    public static function setUploadDir($dir)
    {
        static::$upload_dir = $dir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getUploadDir()
    {
        return static::$upload_dir ?: sys_get_temp_dir();
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
    public function getFile()
    {
        if (isset($this->params['REQUEST_METHOD']) && isset($this->params['CONTENT_TYPE'])) {
            $requestMethod = $this->params['REQUEST_METHOD'];
            $contentType   = $this->params['CONTENT_TYPE'];

            if (strcasecmp($requestMethod, 'POST') === 0 && stripos($contentType, 'multipart/form-data') === 0) {
                if (preg_match('/boundary=(.*)?/', $contentType, $matches)) {
                    list($postData, $this->uploaded_files) = $this->parseMultipartFormData($this->stdin, $matches[1]);
                    parse_str($postData, $post);
                    return $post;
                }
            }
        }
        return false;
    }

    private function parseMultipartFormData($stream, $boundary) {
        $post = "";
        $files = [];
        $field_type = $field_name = $filename = $mimetype = null;
        $in_header = $get_content = false;

        while (!feof($stream)) {
            $get_content = $field_name && !$in_header;
            $buffer = stream_get_line($stream, static::$buffer_size, "\r\n" . ($get_content ? '--'.$boundary : ''));

            if ($in_header && strlen($buffer) == 0) {
                $in_header = false;
            } else {
                if ($get_content) {
                    if ($field_type === 'data') {
                        $post .= (isset($post[0]) ? '&' : '') . $field_name . "=" . urlencode($buffer);
                    } elseif ($field_type === 'file' && $filename) {
                        $tmp_path = $this->getUploadDir().'/'.substr(md5(rand().time()), 0, 16);
                        $err = file_put_contents($tmp_path, $buffer);
                        $files[$field_name] = [
                            'type' => $mime_type ?: 'application/octet-stream',
                            'name' => $filename,
                            'tmp_name' => $tmp_path,
                            'error' => ($err === false) ? true : 0,
                            'size' => filesize($tmp_path),
                        ];
                        $filename = $mime_type = null;
                    }
                    $field_name = $field_type = null;
                } elseif (strpos($buffer, 'Content-Disposition') === 0) {
                    $in_header = true;
                    if (preg_match('/name=\"([^\"]*)\"/', $buffer, $matches)) {
                        $field_name = $matches[1];
                    }
                    if ($is_file = preg_match('/filename=\"([^\"]*)\"/', $buffer, $matches)) {
                        $filename = $matches[1];
                    }
                    $field_type = $is_file ? 'file' : 'data';
                } elseif (strpos($buffer, 'Content-Type') === 0) {
                    $in_header = true;
                    if (preg_match('/Content-Type: (.*)?/', $buffer, $matches)) {
                        $mime_type = trim($matches[1]);
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
        $query   = $this->getQuery();
        $post    = $this->getFile() ?: $this->getPost();
        $cookies = $this->getCookies();

        $server  = ServerRequestFactory::normalizeServer($this->params);
        $headers = ServerRequestFactory::marshalHeaders($server);
        $uri     = ServerRequestFactory::marshalUriFromServer($server, $headers);
        $method  = ServerRequestFactory::get('REQUEST_METHOD', $server, 'GET');

        $request = new ServerRequest($server, $this->uploaded_files, $uri, $method, $this->stdin, $headers);

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
        $query   = $this->getQuery();
        $post    = $this->getFile() ?: $this->getPost();
        $cookies = $this->getCookies();

        return new HttpFoundationRequest($query, $post, [], $cookies, $this->uploaded_files, $this->params, $this->stdin);
    }
}
