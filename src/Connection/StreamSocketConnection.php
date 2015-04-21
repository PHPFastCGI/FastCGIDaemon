<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

class StreamSocketConnection implements ConnectionInterface
{
    use StreamSocketExceptionTrait;

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

        $buffer = @fread($this->socket, $length);

        if (false === $buffer) {
            throw $this->createExceptionFromLastError('fread');
        }

        return $buffer;
    }

    /**
     * {@inheritdoc}
     */
    public function write($buffer)
    {
        if (false === @fwrite($this->socket, $buffer)) {
            throw $this->createExceptionFromLastError('fwrite');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->socket);
        $this->socket = false;
    }
}
