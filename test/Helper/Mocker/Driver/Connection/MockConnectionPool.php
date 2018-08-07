<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionPoolInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnectionPool implements ConnectionPoolInterface
{
    use MockerTrait;

    public function getReadableConnections(int $timeout): array
    {
        return $this->delegateCall('getReadableConnections', func_get_args());
    }

    public function count(): int
    {
        return $this->delegateCall('count', func_get_args());
    }

    public function shutdown(): void
    {
        $this->delegateCall('shutdown', func_get_args());
    }

    public function close(): void
    {
        $this->delegateCall('close', func_get_args());
    }
}
