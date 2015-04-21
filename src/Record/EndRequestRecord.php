<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

class EndRequestRecord extends Record
{
    const FCGI_REQUEST_COMPLETE  = 0;
    const FCGI_CANT_MPX_CONN     = 1;
    const FCGI_OVERLOADED        = 2;
    const FCGI_UNKNOWN_ROLE      = 3;

    protected $appStatus;

    protected $protocolStatus;

    public function __construct($requestId, $appStatus, $protocolStatus)
    {
        $header = new RecordHeader(RecordHeader::FCGI_VERSION_1,
            RecordHeader::FCGI_END_REQUEST, $requestId, 8, 0);

        $this->appStatus      = $appStatus;
        $this->protocolStatus = $protocolStatus;

        $content = pack('NCC3', $appStatus, $protocolStatus, 0, 0, 0);

        parent::__construct($header, $content);
    }

    public function getAppStatus()
    {
        return $this->appStatus;
    }

    public function getProtocolStatus()
    {
        return $this->protocolStatus;
    }
}
