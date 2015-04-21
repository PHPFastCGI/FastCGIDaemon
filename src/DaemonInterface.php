<?php

namespace PHPFastCGI\FastCGI;

use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;
use PHPFastCGI\FastCGIDaemon\Http\Request;

interface DaemonInterface
{
    const FCGI_LISTENSOCK_FILENO = 0;

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
