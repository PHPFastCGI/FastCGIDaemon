<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Record\Record;

trait ConnectionHandlerTrait
{
    protected $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    protected function validateRequestId(Record $record, $requestId)
    {
        $recordRequestId = $record->getHeader()->getRequestId();

        if ($recordRequestId !== $requestId) {
            throw new ProtocolException('Expected request id ' . $requestId .
                ' but got request id ' . $recordRequestId);
        }
    }
}
