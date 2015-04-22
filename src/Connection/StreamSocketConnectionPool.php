<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class StreamSocketConnectionPool implements ConnectionPoolInterface
{
    use StreamSocketExceptionTrait;

    protected $socket = false;
    protected $url;

    public function __construct()
    {
        $this->connect();
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
        while (1) {
            $acceptedSocket = @stream_socket_accept($this->socket);

            if (false === $acceptedSocket) {
                $this->close();
                $this->connect();
            } else {
                break;
            }
        }

        return new StreamSocketConnection($acceptedSocket);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->socket);
        $this->socket = false;
    }

    protected function connect()
    {
        if (false !== $this->socket) {
            $this->close();
        }

        $this->socket = fopen('php://fd/' . DaemonInterface::FCGI_LISTENSOCK_FILENO, 'r');

        if (false === $this->socket) {
            throw $this->createExceptionFromLastError('fopen');
        }
    }
}
