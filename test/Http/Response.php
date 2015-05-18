<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\Http\ResponseInterface;

class Response implements ResponseInterface
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $reasonPhrase;

    /**
     * @var string[]
     */
    protected $headerLines;

    /**
     * @var resource|string|null
     */
    protected $body;

    /**
     * Constructor.
     *
     * @param int                  $statusCode
     * @param string               $reasonPhrase
     * @param string[]             $headerLines
     * @param resource|string|null $body
     */
    public function __construct($statusCode, $reasonPhrase, array $headerLines, $body = null)
    {
        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
        $this->headerLines  = $headerLines;
        $this->body         = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLines()
    {
        return $this->headerLines;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }
}
