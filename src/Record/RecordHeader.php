<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;

class RecordHeader
{
    const FCGI_VERSION_1     = 1;

    const FCGI_BEGIN_REQUEST = 1;
    const FCGI_ABORT_REQUEST = 2;
    const FCGI_END_REQUEST   = 3;
    const FCGI_PARAMS        = 4;
    const FCGI_STDIN         = 5;
    const FCGI_STDOUT        = 6;
    const FCGI_STDERR        = 7;
    const FCGI_DATA          = 8;
    const FCGI_GET_VALUES    = 9;

    protected $version;

    protected $type;

    protected $requestId;

    protected $contentLength;

    protected $paddingLength;

    public function __construct($version, $type, $requestId, $contentLength,
        $paddingLength)
    {
        $this->version       = $version;
        $this->type          = $type;
        $this->requestId     = $requestId;
        $this->contentLength = $contentLength;
        $this->paddingLength = $paddingLength;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getContentLength()
    {
        return $this->contentLength;
    }

    public function getPaddingLength()
    {
        return $this->paddingLength;
    }

    public static function readFromConnection(ConnectionInterface $connection)
    {
        $headerData = $connection->read(8);

        $format = (
            'Cversion/'       .
            'Ctype/'          .
            'nrequestId/'     .
            'ncontentLength/' .
            'CpaddingLength/' .
            'Creserved'
        );

        $headerParameters = unpack($format, $headerData);

        return new static($headerParameters['version'],
            $headerParameters['type'], $headerParameters['requestId'],
            $headerParameters['contentLength'],
            $headerParameters['paddingLength']);
    }

    public function writeToConnection(ConnectionInterface $connection)
    {
        $headerData = pack('CCnnCC', $this->getVersion(), $this->getType(),
            $this->getRequestId(), $this->getContentLength(),
            $this->getPaddingLength(), 0);

        $connection->write($headerData);
    }
}
