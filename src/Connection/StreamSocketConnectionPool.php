<?php

namespace PHPFastCGI\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerFactoryInterface;
use PHPFastCGI\FastCGIDaemon\ConnectionHandler\ConnectionHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The default implementation of the ConnectionPoolInterface using stream
 * sockets.
 */
class StreamSocketConnectionPool implements ConnectionPoolInterface, LoggerAwareInterface
{
    use StreamSocketExceptionTrait;
    use LoggerAwareTrait;

    /**
     * @var resource
     */
    protected $serverSocket;

    /**
     * @var resource[]
     */
    protected $clientSockets;

    /**
     * @var Connection[]
     */
    protected $connections;

    /**
     * @var ConnectionHandlerInterface[]
     */
    protected $connectionHandlers;

    /**
     * Constructor.
     *
     * @param resource        $socket The stream socket to accept connections from
     * @param LoggerInterface $logger A logger to use
     */
    public function __construct($socket, LoggerInterface $logger = null)
    {
        $this->setLogger((null === $logger) ? new NullLogger() : $logger);

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

        $write  = [];
        $except = [];

        while (1) {
            $read = array_merge(['pool' => $this->serverSocket], $this->clientSockets);

            if (false === stream_select($read, $write, $except, $timeoutLoopSeconds, $timeoutLoopMicroseconds)) {
                $lastError = error_get_last();
     
                if (null === $lastError) {
                    $this->logger->emergency('stream_select() returned false');
                } else {
                    $this->logger->emergency($lastError['message']);
                }

                break;
            }

            foreach (array_keys($read) as $id) {
                if ('pool' === $id) {
                    $this->acceptConnection($connectionHandlerFactory);
                } else {
                    $this->connectionHandlers[$id]->ready();
                }
            }

            $this->removeClosedConnections();
        }
    }

    /**
     * Accept incoming connections from the stream socket.
     * 
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory The factory used to create connection handlers
     */
    protected function acceptConnection(ConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        $clientSocket = stream_socket_accept($this->serverSocket);

        stream_set_blocking($clientSocket, 0);

        $connection = new StreamSocketConnection($clientSocket);
        $handler    = $connectionHandlerFactory->createConnectionHandler($connection);

        $id = spl_object_hash($connection);

        $this->clientSockets[$id]      = $clientSocket;
        $this->connections[$id]        = $connection;
        $this->connectionHandlers[$id] = $handler;
    }

    /**
     * Remove closed connections.
     */
    protected function removeClosedConnections()
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
