<?php

namespace PHPFastCGI\FastCGI;

use PHPFastCGI\FastCGIDaemon\Connection\StreamSocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\SingleplexedResponderConnectionHandlerFactory;

class StreamSocketDaemon extends AbstractSingleplexedDaemon implements DaemonInterface
{
    /**
     * Start the daemon listening on a socket stream
     * 
     * @param string $url The socket stream URL
     * 
     * @throws ConnectionException If the daemon cannot listen on this stream
     */
    public function __construct($url)
    {
        $connectionPool           = new StreamSocketConnectionPool($url);
        $connectionHandlerFactory = new SingleplexedResponderConnectionHandlerFactory();

        parent::__construct($connectionPool, $connectionHandlerFactory);
    }
}
