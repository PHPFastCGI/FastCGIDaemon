<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

interface ConnectionHandlerInterface
{
    /**
     * Triggered when the connection the handler was assigned to is ready to
     * be read.
     */
    public function ready();

    /**
     * Closes the connection handler and free's associated resources.
     */
    public function close();
}
