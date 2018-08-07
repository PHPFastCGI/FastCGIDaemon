<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\Driver;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;
use PHPFastCGI\FastCGIDaemon\Driver\DriverContainerInterface;
use PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker\MockerTrait;

class MockDriverContainer implements DriverContainerInterface
{
    use MockerTrait;

    public function getFactory(string $driver): DaemonFactoryInterface
    {
        return $this->delegateCall('getFactory', func_get_args());
    }
}
