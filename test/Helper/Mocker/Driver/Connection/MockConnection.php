<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnection implements ConnectionInterface
{
    use MockerTrait;

    public function read($length)
    {
        return $this->delegateCall('read', func_get_args());
    }

    public function write($buffer)
    {
        return $this->delegateCall('write', func_get_args());
    }

    public function isClosed()
    {
        return $this->delegateCall('isClosed', func_get_args());
    }

    public function close()
    {
        return $this->delegateCall('close', func_get_args());
    }
}
