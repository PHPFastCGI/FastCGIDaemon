<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

/**
 * Objects implementing the connection handler interface are usually
 * instantiated via some method with an incoming connection and a kernel. The
 * handler is notified when these connections are ready to be read or closed and
 * should handle communication between the incoming connection and the kernel.
 */
interface ConnectionHandlerInterface
{
    /**
     * Triggered when the connection the handler was assigned to is ready to
     * be read.
     */
    public function ready();

    /**
     * Gracefully shutdown the connection being handled. Usually triggered
     * following a SIGINT.
     */
    public function shutdown();

    /**
     * Closes the connection handler and free's associated resources.
     */
    public function close();
}
