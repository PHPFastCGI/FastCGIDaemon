<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class MockDaemonFactory implements DaemonFactoryInterface
{
    use MockerTrait;

    public function createDaemon(KernelInterface $kernel, DaemonOptions $options)
    {
        return $this->delegateCall('createDaemon', func_get_args());
    }

    public function createTcpDaemon(KernelInterface $kernel, DaemonOptions $options, $port, $host = 'localhost')
    {
        return $this->delegateCall('createTcpDaemon', func_get_args());
    }
}
