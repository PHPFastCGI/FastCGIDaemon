<?php

namespace PHPFastCGI\FastCGIDaemon;

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
     * Run the daemon
     * 
     * @param KernelInterface $kernel The kernel to handle the requests
     */
    public function run(KernelInterface $kernel);
}
