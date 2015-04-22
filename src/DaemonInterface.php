<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;
use PHPFastCGI\FastCGIDaemon\Http\Request;

interface DaemonInterface
{
    // Socket descriptor
    const FCGI_LISTENSOCK_FILENO = 0;

    // Versions
    const FCGI_VERSION_1         = 1;

    // Records
    const FCGI_BEGIN_REQUEST     = 1;
    const FCGI_ABORT_REQUEST     = 2;
    const FCGI_END_REQUEST       = 3;
    const FCGI_PARAMS            = 4;
    const FCGI_STDIN             = 5;
    const FCGI_STDOUT            = 6;
    const FCGI_STDERR            = 7;
    const FCGI_DATA              = 8;
    const FCGI_GET_VALUES        = 9;
    
    // Roles
    const FCGI_RESPONDER         = 1;
    const FCGI_AUTHORIZER        = 2;
    const FCGI_FILTER            = 3;

    // Flags
    const FCGI_KEEP_CONNECTION   = 1;

    // Statuses
    const FCGI_REQUEST_COMPLETE  = 0;
    const FCGI_CANT_MPX_CONN     = 1;
    const FCGI_OVERLOADED        = 2;
    const FCGI_UNKNOWN_ROLE      = 3;

    /**
     * This is a blocking function which waits for and retrieves the next
     * request to the daemon. Requests may be rejected from the daemon for many
     * reasons. The $returnOnError parameter configures whether this method
     * should return a null value if it encounters an error parsing a request.
     * If this parameter is FALSE, the daemon will only attempt to read another
     * request if the error it encountered was recoverable and did not prevent
     * normal operation. If an error is encountered that does prevent the daemon
     * from receiving new requests, in either case, a ConnectionException will
     * be thrown.
     * 
     * @param  bool $returnOnError True to return on a recoverable error
     * 
     * @throws ConnectionException If the daemon encounters an unrecoverable error
     * @return Request|null        The request object or null (see description)
     */
    public function getRequest($returnOnError = false);
}
