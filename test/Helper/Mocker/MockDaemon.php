<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class MockDaemon implements DaemonInterface
{
    use MockerTrait;

    public function run(): void
    {
        $this->delegateCall('run', func_get_args());
    }

    public function flagShutdown(string $message = null): void
    {
        $this->delegateCall('flagShutdown', func_get_args());
    }
}
