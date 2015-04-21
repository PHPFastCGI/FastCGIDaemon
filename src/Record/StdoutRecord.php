<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

class StdoutRecord extends Record
{
    public function __construct($requestId, $content)
    {
        $contentLength = (null === $content) ? 0 : strlen($content);

        $header = new RecordHeader(RecordHeader::FCGI_VERSION_1,
            RecordHeader::FCGI_STDOUT, $requestId, $contentLength, 0);

        parent::__construct($header, $content);
    }
}
