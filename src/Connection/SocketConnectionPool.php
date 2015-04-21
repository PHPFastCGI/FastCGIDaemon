<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

class SocketConnectionPool implements ConnectionPoolInterface
{
    use SocketExceptionTrait;

    protected $socket = false;

    public function __construct($port)
    {
        $this->socket = socket_create_listen($port);

        if (false === $this->socket) {
            throw $this->createExceptionFromLastError('socket_create_listen');
        }
    }

    public function __destruct()
    {
        if (false !== $this->socket) {
            $this->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $acceptedSocket = socket_accept($this->socket);

        if (false === $acceptedSocket) {
            throw $this->createExceptionFromLastError('socket_accept');
        }

        return new SocketConnection($acceptedSocket);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        socket_close($this->socket);
        $this->socket = false;
    }
}
