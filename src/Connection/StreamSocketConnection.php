<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

class StreamSocketConnection implements ConnectionInterface
{
    use StreamSocketExceptionTrait;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var bool
     */
    protected $closed;

    /**
     * Constructor.
     * 
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->closed = false;
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

        $buffer = fread($this->socket, $length);

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
        if (false === fwrite($this->socket, $buffer)) {
            throw $this->createExceptionFromLastError('fwrite');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isClosed()
    {
        return $this->closed;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->socket);

        $this->socket = false;
        $this->closed = true;
    }
}
