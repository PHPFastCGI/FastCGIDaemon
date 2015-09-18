<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class MockKernel implements KernelInterface
{
    use MockerTrait;

    public function handleRequest(RequestInterface $request)
    {
        return $this->delegateCall('handleRequest', func_get_args());
    }
}
