<?php

namespace PHPFastCGI\FastCGIDaemon;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Wraps a callback (such as a closure, function or class and method pair) as an
 * implementation of the kernel interface.
 */
class CallbackWrapper implements KernelInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructor.
     *
     * @param callable $handler The handler callback to wrap
     *
     * @throws \InvalidArgumentException When not given callable callback
     */
    public function __construct($handler)
    {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException('Handler callback is not callable');
        }

        $this->callback = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        return call_user_func($this->callback, $request);
    }
}
