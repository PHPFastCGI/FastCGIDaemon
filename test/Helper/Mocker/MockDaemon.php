<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

class MockDaemon implements DaemonInterface
{
    use MockerTrait;

    public function run()
    {
        return $this->delegateCall('run', func_get_args());
    }

    public function flagShutdown($message = null)
    {
        return $this->delegateCall('flagShutdown', func_get_args());
    }
}
