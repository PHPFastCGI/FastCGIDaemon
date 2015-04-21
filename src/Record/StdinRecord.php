<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

class StdinRecord extends Record
{
    protected $endRecord;

    public function __construct(RecordHeader $header, $content)
    {
        $this->endRecord = ($header->getContentLength() < 1);

        parent::__construct($header, $content);
    }

    public function isEndRecord()
    {
        return $this->endRecord;
    }
}
