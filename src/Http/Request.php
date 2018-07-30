<?php

namespace PHPFastCGI\FastCGIDaemon\Http;

use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

/**
 * The default implementation of the RequestInterface.
 */
final class Request implements RequestInterface
{
    /**
     * @var ServerRequestCreatorInterface|null
     */
    private static $serverRequestCreator = null;

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

    public static function setServerRequestCreator(ServerRequestCreatorInterface $serverRequestCreator): void
    {
        self::$serverRequestCreator = $serverRequestCreator;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Remove all uploaded files
     */
    public function cleanUploadedFiles(): void
    {
        foreach ($this->uploadedFiles as $file) {
            @unlink($file['tmp_name']);
        }
    }

    /**
     * Set a buffer size to read uploaded files
     */
    public static function setBufferSize(int $size): void
    {
        static::$bufferSize = $size;
    }

    public static function getBufferSize(): int
    {
        return static::$bufferSize;
    }

    public static function setUploadDir(string $dir): void
    {
        static::$uploadDir = $dir;
    }

    public static function getUploadDir(): string
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
                if (preg_match('/boundary=(?P<quote>[\'"]?)(.*)(?P=quote)/', $contentType, $matches)) {
                    list($postData, $this->uploadedFiles) = $this->parseMultipartFormData($this->stdin, $matches[2]);
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
            $buffer = stream_get_line($stream, static::$bufferSize,  "\n" . ($getContent ? '--'.$boundary : ''));
            $buffer = trim($buffer, "\r");

            // Find the empty line between headers and body
            if ($inHeader && strlen($buffer) == 0) {
                $inHeader = false;

                continue;
            }

            if ($getContent) {
                if ($fieldType === 'data') {
                    $post .= (isset($post[0]) ? '&' : '') . $fieldName . "=" . urlencode($buffer);
                } elseif ($fieldType === 'file' && $filename) {
                    $tmpPath = @tempnam($this->getUploadDir(), 'fastcgi_upload');
                    $err = file_put_contents($tmpPath, $buffer);
                    $this->addFile($files, $fieldName, $filename, $tmpPath, $mimeType, false === $err);
                    $filename = $mimeType = null;
                }
                $fieldName = $fieldType = null;

                continue;
            }

            // Assert: We may be in the header, lets try to find 'Content-Disposition' and 'Content-Type'.
            if (strpos($buffer, 'Content-Disposition') === 0) {
                $inHeader = true;
                if (preg_match('/name=\"([^\"]*)\"/', $buffer, $matches)) {
                    $fieldName = $matches[1];
                }
                if (preg_match('/filename=\"([^\"]*)\"/', $buffer, $matches)) {
                    $filename = $matches[1];
                    $fieldType = 'file';
                } else {
                    $fieldType = 'data';
                }
            } elseif (strpos($buffer, 'Content-Type') === 0) {
                $inHeader = true;
                if (preg_match('/Content-Type: (.*)?/', $buffer, $matches)) {
                    $mimeType = trim($matches[1]);
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
        if (null === self::$serverRequestCreator) {
            throw new \RuntimeException('You need to add an object of \Nyholm\Psr7Server\ServerRequestCreatorInterface to \PHPFastCGI\FastCGIDaemon\Http\Request::setServerRequestCreator to use PSR-7 requests. Please install and read more at https://github.com/nyholm/psr7-server');
        }

        $server  = $this->params;
        $query   = $this->getQuery();
        $post    = $this->getPost();
        $cookies = $this->getCookies();

        $server  = ServerRequestFactory::normalizeServer($this->params);
        $headers = ServerRequestFactory::marshalHeaders($server);
        $uri     = ServerRequestFactory::marshalUriFromServer($server, $headers);
        $method  = ServerRequestFactory::get('REQUEST_METHOD', $server, 'GET');

        $files = $this->uploadedFiles;
        $this->preparePsr7UploadedFiles($files);

        $request = new ServerRequest($server, $files, $uri, $method, $this->stdin, $headers);

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

    /**
     * Add a file to the $files array
     */
    private function addFile(array &$files, string $fieldName, string $filename, string $tmpPath, string $mimeType, bool $err): void
    {
        $data = [
            'type' => $mimeType ?: 'application/octet-stream',
            'name' => $filename,
            'tmp_name' => $tmpPath,
            'error' => $err ? UPLOAD_ERR_CANT_WRITE : UPLOAD_ERR_OK,
            'size' => filesize($tmpPath),
        ];

        $parts = preg_split('|(\[[^\]]*\])|', $fieldName, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $count = count($parts);
        if (1 === $count) {
            $files[$fieldName] = $data;
        } else {
            $current = &$files;
            foreach ($parts as $i => $part) {
                if ($part === '[]') {
                    $current[] = $data;
                    continue;
                }

                $trimmedMatch = trim($part, '[]');
                if ($i === $count -1) {
                    $current[$trimmedMatch] = $data;
                } else {
                    $current = &$current[$trimmedMatch];
                }
            }
        }
    }

    private function preparePsr7UploadedFiles(array &$files)
    {
        if (isset($files['tmp_name'])) {
            $files = createUploadedFile($files);
        } else {
            foreach ($files as &$file) {
                $this->preparePsr7UploadedFiles($file);
            }
        }
    }
}
