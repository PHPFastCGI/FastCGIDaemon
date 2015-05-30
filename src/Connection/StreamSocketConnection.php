<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

/**
 * The default implementation of the ConnectionInterface using stream socket
 * resources.
 */
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
     * @param resource $socket The stream socket to wrap
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->closed = false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if ($this->isClosed()) {
            throw new \Exception('Connection has been closed');
        }

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
        if ($this->isClosed()) {
            throw new \Exception('Connection has been closed');
        }

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
        if (!$this->isClosed()) {
            fclose($this->socket);

            $this->socket = null;
            $this->closed = true;
        }
    }
}
