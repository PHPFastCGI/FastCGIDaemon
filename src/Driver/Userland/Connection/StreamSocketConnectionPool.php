<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection;

/**
 * The default implementation of the ConnectionPoolInterface using stream
 * sockets.
 */
final class StreamSocketConnectionPool implements ConnectionPoolInterface
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
     * @var ConnectionInterface[]
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
        stream_set_blocking($socket, false);

        $this->serverSocket  = $socket;
        $this->clientSockets = [];
        $this->connections   = [];

        $this->shutdown = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getReadableConnections(int $timeout): array
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
    public function count(): int
    {
        $this->removeClosedConnections();

        return count($this->connections);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        $this->shutdown = true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
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
    private function selectConnections(&$readSockets, int $timeout): void
    {
        // stream_select will not always preserve array keys
        // call it with a (deep) copy so the original is preserved
        $read = [];
        foreach ($readSockets as $id => $socket) {
            $read[] = $socket;
        }
        $writeSockets = $exceptSockets = [];

        if (false === @stream_select($read, $writeSockets, $exceptSockets, $timeout)) {
            $error = error_get_last();

            if (false === stripos($error['message'], 'interrupted system call')) {
                throw new \RuntimeException('stream_select failed: '.$error['message']);
            }

            $readSockets = [];
        } else {
            $res = [];
            foreach($read as $socket) {
                $res[array_search($socket, $readSockets)] = $socket;
            }
            $readSockets = $res;
        }
    }

    /**
     * Accept incoming connections from the server stream socket.
     */
    private function acceptConnection(): void
    {
        $clientSocket = @stream_socket_accept($this->serverSocket);

        if (false !== $clientSocket) {
            stream_set_blocking($clientSocket, false);

            $connection = new StreamSocketConnection($clientSocket);

            $id = spl_object_hash($connection);

            $this->clientSockets[$id] = $clientSocket;
            $this->connections[$id]   = $connection;
        }
    }

    private function removeClosedConnections(): void
    {
        foreach ($this->connections as $id => $connection) {
            if ($connection->isClosed()) {
                unset($this->clientSockets[$id]);
                unset($this->connections[$id]);
            }
        }
    }
}
