<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnection implements ConnectionInterface
{
    use MockerTrait;

    public function read(int $length): string
    {
        $this->delegateCall('read', func_get_args());
    }

    public function write(string $buffer): void
    {
        $this->delegateCall('write', func_get_args());
    }

    public function isClosed(): bool
    {
        return $this->delegateCall('isClosed', func_get_args());
    }

    public function close(): void
    {
        $this->delegateCall('close', func_get_args());
    }
}
