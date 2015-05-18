<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestEnvironmentInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class Kernel implements KernelInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructor
     * 
     * @param callable $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function handleRequest(RequestEnvironmentInterface $requestEnvironment)
    {
        return call_user_func($this->callback, $requestEnvironment);
    }
}
