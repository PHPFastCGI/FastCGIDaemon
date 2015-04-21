<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Exception\AbortRequestException;

class Record {
    protected $header;

    protected $content;

    public function __construct(RecordHeader $header, $content)
    {
        $this->header  = $header;
        $this->content = $content;
    }

    /**
     * Returns the record header
     * 
     * @return RecordHeader The header for the record
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Returns the record content
     * 
     * @return string The content buffer
     */
    public function getContent()
    {
        return $this->content;
    }

    public static function readFromConnection(ConnectionInterface $connection)
    {
        $header = RecordHeader::readFromConnection($connection);

        $content = $connection->read($header->getContentLength());
        $connection->read($header->getPaddingLength());

        switch ($header->getType()) {
            case RecordHeader::FCGI_BEGIN_REQUEST:
                return new BeginRequestRecord($header, $content);
            case RecordHeader::FCGI_PARAMS:
                return new ParamRecord($header, $content);
            case RecordHeader::FCGI_STDIN:
                return new StdinRecord($header, $content);
            case RecordHeader::FCGI_ABORT_REQUEST:
                $exception = new AbortRequestException(
                    'Received FCGI_ABORT_REQUEST');
                $exception->setRequestId($header->getRequestId());
                throw $exception;
        }

        return new static($header, $content);
    }

    public function writeToConnection(ConnectionInterface $connection)
    {
        $this->getHeader()->writeToConnection($connection);

        $content = $this->getContent();

        if (null !== $content) {
            $connection->write($content);
        }
    }
}
