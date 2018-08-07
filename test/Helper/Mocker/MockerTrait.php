<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Helper\Mocker;

trait MockerTrait
{
    /**
     * Associative array of callbacks given in the constructor.
     *
     * @var array
     */
    private $callbacks;

    /**
     * Numeric array of the delegated calls.
     *
     * @var array
     */
    private $delegatedCalls;

    /**
     * Constructor.
     *
     * @param array $callbacks Associative array of callbacks where the key of
     *                         each array entry is the method name to bind the callback to.
     */
    public function __construct(array $callbacks = null)
    {
        $this->callbacks = $callbacks ?: [];

        $this->delegatedCalls = [];
    }

    /**
     * Delegates a call to a configured method callback.
     *
     * @param string $method    The name of the method
     * @param array  $arguments The arguments to pass to the callback
     *
     * @return mixed The return value from the callback
     *
     * @throws \InvalidArgumentException When no callback is set for the method
     */
    protected function delegateCall(string $method, array $arguments)
    {
        if (!isset($this->callbacks[$method])) {
            throw new \InvalidArgumentException('Method not configured: '.$method);
        }

        $this->delegatedCalls[] = [$method, $arguments];

        if (false !== $this->callbacks[$method]) {
            return call_user_func_array($this->callbacks[$method], $arguments);
        }
    }

    /**
     * Returns a numeric array containing the delegated calls. Each delegated
     * call is represented as a two element array where the first element is the
     * method and the second element is the argument list.
     *
     * @return array The delegated call list
     */
    public function getDelegatedCalls(): array
    {
        return $this->delegatedCalls;
    }
}
