<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerInterface;

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
     * @var ConnectionHandlerInterface[]
     */
    private $connectionHandlers;

    /**
     * Constructor.
     * 
     * @param resource $socket The stream socket to accept connections from
     */
    public function __construct($socket)
    {
        stream_set_blocking($socket, 0);

        $this->serverSocket       = $socket;
        $this->clientSockets      = [];
        $this->connections        = [];
        $this->connectionHandlers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function operate(ConnectionHandlerFactoryInterface $connectionHandlerFactory, $timeoutLoop)
    {
        $timeoutLoopSeconds      = (int) floor($timeoutLoop);
        $timeoutLoopMicroseconds = (int) (($timeoutLoop - $timeoutLoopSeconds) * 1000000);

        $write = $except = [];

        $read = array_merge(['pool' => $this->serverSocket], $this->clientSockets);

        if (false === @stream_select($read, $write, $except, $timeoutLoopSeconds, $timeoutLoopMicroseconds)) {
            $error = error_get_last();

            if (false === stripos($error['message'], 'interrupted system call')) {
                throw new \RuntimeException('stream_select failed: '.$error['message']);
            }
        } else {
            foreach (array_keys($read) as $id) {
                if ('pool' === $id) {
                    $this->acceptConnection($connectionHandlerFactory);
                } else {
                    $this->connectionHandlers[$id]->ready();
                }
            }

            $this->removeConnections();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        foreach ($this->connectionHandlers as $connectionHandler) {
            $connectionHandler->shutdown();
        }

        $this->removeConnections();

        while (count($this->connections) > 0) {
            $write = $except = [];

            $read = $this->clientSockets;

            stream_select($read, $write, $except, 1);

            foreach (array_keys($read) as $id) {
                $this->connectionHandlers[$id]->ready();
            }

            $this->removeConnections();
        }

        fclose($this->serverSocket);
    }

    /**
     * Accept incoming connections from the stream socket.
     *
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory The factory used to create connection handlers
     */
    private function acceptConnection(ConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        $clientSocket = @stream_socket_accept($this->serverSocket);

        if (false !== $clientSocket) {
            stream_set_blocking($clientSocket, 0);

            $connection = new StreamSocketConnection($clientSocket);
            $handler    = $connectionHandlerFactory->createConnectionHandler($connection);

            $id = spl_object_hash($connection);

            $this->clientSockets[$id]      = $clientSocket;
            $this->connections[$id]        = $connection;
            $this->connectionHandlers[$id] = $handler;
        }
    }

    /**
     * Remove connections.
     */
    private function removeConnections()
    {
        foreach ($this->connections as $id => $connection) {
            if ($connection->isClosed()) {
                unset($this->clientSockets[$id]);
                unset($this->connections[$id]);
                unset($this->connectionHandlers[$id]);
            }
        }
    }
}
