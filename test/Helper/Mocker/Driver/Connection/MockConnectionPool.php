<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\Connection;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionPoolInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnectionPool implements ConnectionPoolInterface
{
    use MockerTrait;

    public function getReadableConnections($timeout)
    {
        return $this->delegateCall('getReadableConnections', func_get_args());
    }

    public function count()
    {
        return $this->delegateCall('count', func_get_args());
    }

    public function shutdown()
    {
        return $this->delegateCall('shutdown', func_get_args());
    }

    public function close()
    {
        return $this->delegateCall('close', func_get_args());
    }
}
