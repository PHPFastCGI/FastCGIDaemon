<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler;

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
     * 
     * @return int The number of requests dispatched during the function call
     */
    public function ready();

    /**
     * Gracefully shutdown the connection being handled.
     */
    public function shutdown();

    /**
     * Closes the connection handler and free's associated resources. Calling
     * this method must also close the connection object that was being handled.
     */
    public function close();

    /**
     * Returns true if the connection handler has been closed and false if it
     * is still active.
     * 
     * @return bool
     */
    public function isClosed();
}
