<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\RequestEnvironmentInterface;
use PHPFastCGI\FastCGIDaemon\Http\ResponseInterface;
use PHPFastCGI\FastCGIDaemon\KernelInterface;

class KernelMock implements KernelInterface
{
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    protected $testCase;

    /**
     * @var RequestEnvironmentInterface
     */
    protected $expectedRequestEnvironment;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param \PHPUnit_Framework_TestCase $testCase
     * @param RequestEnvironmentInterface $expectedRequestEnvironment
     * @param ResponseInterface           $response
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase, RequestEnvironmentInterface $expectedRequestEnvironment, ResponseInterface $response)
    {
        $this->testCase                   = $testCase;
        $this->expectedRequestEnvironment = $expectedRequestEnvironment;
        $this->response                   = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestEnvironmentInterface $requestEnvironment)
    {
        // Only checking params & body here (request parsing is tested in the environment builder)
        $this->testCase->assertEquals($this->expectedRequestEnvironment->getServer(), $requestEnvironment->getServer());

        $expectedBody = $this->expectedRequestEnvironment->getBody();
        $body         = $requestEnvironment->getBody();

        if (null === $expectedBody) {
            $this->testCase->assertNull($body);
        } else {
            if (is_resource($expectedBody)) {
                $expectedBody = stream_get_contents($expectedBody);
            }

            if (is_resource($body)) {
                $body = stream_get_contents($body);
            }

            // Test body lengths before testing full body (looks ugly in readout when it fails)
            $this->testCase->assertEquals(strlen($expectedBody), strlen($body));
            $this->testCase->assertEquals($expectedBody, $body);
        }

        return $this->response;
    }
}
