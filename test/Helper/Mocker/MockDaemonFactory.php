<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;
use PHPFastCGI\FastCGIDaemon\DaemonInterface;
use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class MockDaemonFactory implements DaemonFactoryInterface
{
    use MockerTrait;

    public function createDaemon(KernelInterface $kernel, DaemonOptions $options, int $fd = DaemonInterface::FCGI_LISTENSOCK_FILENO): DaemonInterface
    {
        return $this->delegateCall('createDaemon', func_get_args());
    }

    public function createTcpDaemon(KernelInterface $kernel, DaemonOptions $options, string $host = 'localhost', int $port): DaemonInterface
    {
        return $this->delegateCall('createTcpDaemon', func_get_args());
    }

    public function createDaemonFromStreamSocket(KernelInterface $kernel, DaemonOptions $options, int $socket): DaemonInterface
    {
        return $this->delegateCall('createDaemonFromStreamSocket', func_get_args());
    }
}
