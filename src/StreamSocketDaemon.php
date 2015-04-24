<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\SingleplexedResponderConnectionHandlerFactory;

class StreamSocketDaemon extends SingleplexedDaemon implements DaemonInterface
{
    /**
     * Start the daemon listening on a socket stream
     * 
     * @param string $path The path to the socket file
     * 
     * @throws ConnectionException If the daemon cannot listen on this stream
     */
    public function __construct()
    {
        $connectionPool           = new StreamSocketConnectionPool();
        $connectionHandlerFactory = new SingleplexedResponderConnectionHandlerFactory();

        parent::__construct($connectionPool, $connectionHandlerFactory);
    }
}
