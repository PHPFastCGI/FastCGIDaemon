<?php

namespace PHPFastCGI\FastCGI;

use PHPFastCGI\FastCGIDaemon\Connection\SocketConnectionPool;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\SingleplexedResponderConnectionHandlerFactory;

class SocketDaemon extends AbstractSingleplexedDaemon implements DaemonInterface
{
    /**
     * Start the daemon listening on the specified port
     * 
     * @param int $port The port to listen on for incoming connections
     * 
     * @throws ConnectionException If the daemon cannot listen on this port
     */
    public function __construct($port = DaemonInterface::FCGI_LISTENSOCK_FILENO)
    {
        $connectionPool           = new SocketConnectionPool($port);
        $connectionHandlerFactory = new SingleplexedResponderConnectionHandlerFactory();

        parent::__construct($connectionPool, $connectionHandlerFactory);
    }
}
