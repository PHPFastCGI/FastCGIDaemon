<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;

class BeginRequestRecord extends Record
{
    const FCGI_RESPONDER       = 1;
    const FCGI_AUTHORIZER      = 2;
    const FCGI_FILTER          = 3;

    const FCGI_KEEP_CONNECTION = 1;

    protected $role;

    protected $flags;

    public function __construct(RecordHeader $header, $content)
    {
        if ($header->getContentLength() !== 8) {
            throw new ProtocolException(
                'Begin request record requires 8 bytes of content, got ' .
                $contentLength);
        }

        $contentFormat = (
            'nrole/' .
            'Cflags/' .
            'C5reserved'
        );

        $parameters = unpack($contentFormat, $content);

        $this->role  = $parameters['role'];
        $this->flags = $parameters['flags'];

        parent::__construct($header, $content);
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function keepConnection()
    {
        return $this->flags & self::FCGI_KEEP_CONNECTION;
    }
}
