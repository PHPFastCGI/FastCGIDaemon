<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

class SocketConnection implements ConnectionInterface
{
    use SocketExceptionTrait;

    protected $socket = false;

    public function __construct($socket)
    {
        $this->socket = $socket;
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
    public function read($length)
    {
        if (0 === $length) {
            return true;
        }

        $buffer = @socket_read($this->socket, $length);

        if (false === $buffer) {
            throw $this->createExceptionFromLastError('socket_read ');
        }

        return $buffer;
    }

    /**
     * {@inheritdoc}
     */
    public function write($buffer)
    {
        if (false === @socket_write($this->socket, $buffer)) {
            throw $this->createExceptionFromLastError('socket_write');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        @socket_close($this->socket);
        $this->socket = false;
    }
}
