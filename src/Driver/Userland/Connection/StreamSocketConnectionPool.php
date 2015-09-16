<?php

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection;

/**
 * The default implementation of the ConnectionPoolInterface using stream
 * sockets.
 */
class StreamSocketConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var resource
     */
    private $serverSocket;

    /**
     * @var resource[]
     */
    private $clientSockets;

    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var bool
     */
    private $shutdown;

    /**
     * Constructor.
     * 
     * @param resource $socket The stream socket to accept connections from
     */
    public function __construct($socket)
    {
        stream_set_blocking($socket, 0);

        $this->serverSocket  = $socket;
        $this->clientSockets = [];
        $this->connections   = [];

        $this->shutdown = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getReadableConnections($timeout)
    {
        $this->removeClosedConnections();

        $readSockets = $this->clientSockets;

        if (!$this->shutdown) {
            $readSockets['pool'] = $this->serverSocket;
        }

        $this->selectConnections($readSockets, $timeout);

        if (isset($readSockets['pool'])) {
            $this->acceptConnection();
            unset($readSockets['pool']);
        }

        $readableConnections = [];

        foreach (array_keys($readSockets) as $id) {
            $readableConnections[$id] = $this->connections[$id];
        }

        return $readableConnections;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->removeClosedConnections();

        return count($this->connections);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        $this->shutdown = true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->removeClosedConnections();

        foreach ($this->connections as $id => $connection) {
            $connection->close();

            unset($this->clientSockets[$id]);
            unset($this->connections[$id]);
        }

        fclose($this->serverSocket);
    }

    /**
     * Uses the stream select function to eliminate all non-readable sockets
     * from the read sockets parameter.
     * 
     * @param resource[] $readSockets The sockets to test for readability (output parameter)
     * @param int        $timeout     The stream select call timeout
     */
    private function selectConnections(&$readSockets, $timeout)
    {
        $writeSockets = $exceptSockets = [];

        if (false === @stream_select($readSockets, $writeSockets, $exceptSockets, $timeout)) {
            $error = error_get_last();

            if (false === stripos($error['message'], 'interrupted system call')) {
                throw new \RuntimeException('stream_select failed: '.$error['message']);
            }

            $readSockets= [];
        }
    }

    /**
     * Accept incoming connections from the server stream socket.
     */
    private function acceptConnection()
    {
        $clientSocket = @stream_socket_accept($this->serverSocket);

        if (false !== $clientSocket) {
            stream_set_blocking($clientSocket, 0);

            $connection = new StreamSocketConnection($clientSocket);

            $id = spl_object_hash($connection);

            $this->clientSockets[$id] = $clientSocket;
            $this->connections[$id]   = $connection;
        }
    }

    /**
     * Remove connections.
     */
    private function removeClosedConnections()
    {
        foreach ($this->connections as $id => $connection) {
            if ($connection->isClosed()) {
                unset($this->clientSockets[$id]);
                unset($this->connections[$id]);
            }
        }
    }
}
