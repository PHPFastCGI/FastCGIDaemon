<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnectionHandler implements ConnectionHandlerInterface
{
    use MockerTrait;

    public function ready()
    {
        return $this->delegateCall('ready', func_get_args());
    }

    public function shutdown()
    {
        return $this->delegateCall('shutdown', func_get_args());
    }

    public function close()
    {
        return $this->delegateCall('close', func_get_args());
    }

    public function isClosed()
    {
        return $this->delegateCall('isClosed', func_get_args());
    }
}
