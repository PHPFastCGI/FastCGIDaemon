<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\KernelInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\Connection\ConnectionInterface;
use PHPFastCGI\FastCGIDaemon\Driver\Userland\ConnectionHandler\ConnectionHandlerFactoryInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    use MockerTrait;

    public function createConnectionHandler(KernelInterface $kernel, ConnectionInterface $connection)
    {
        return $this->delegateCall('createConnectionHandler', func_get_args());
    }
}
