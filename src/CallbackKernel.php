<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;

/**
 * Wraps a callback (such as a closure, function or class and method pair) as an
 * implementation of the kernel interface.
 */
final class CallbackKernel implements KernelInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * Constructor.
     *
     * @param callable $handler The handler callback to wrap
     *
     * @throws \InvalidArgumentException When not given callable callback
     */
    public function __construct(callable $handler)
    {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException('Handler callback is not callable');
        }

        $this->callback = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request)
    {
        return call_user_func($this->callback, $request);
    }
}
