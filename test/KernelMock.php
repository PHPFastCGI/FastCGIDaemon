<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Helper mock of a Kernel that can be used to check that the expected request
 * is received and then provide a predetermined response.
 */
class KernelMock implements KernelInterface
{
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    protected $testCase;

    /**
     * @var ServerRequestInterface
     */
    protected $expectedRequest;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param \PHPUnit_Framework_TestCase $testCase
     * @param ServerRequestInterface      $expectedRequest
     * @param ResponseInterface           $response
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase, ServerRequestInterface $expectedRequest, ResponseInterface $response)
    {
        $this->testCase        = $testCase;
        $this->expectedRequest = $expectedRequest;
        $this->response        = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // Only checking params & body here (request parsing is tested in the environment builder)
        $this->testCase->assertEquals($this->expectedRequest->getServerParams(), $request->getServerParams());

        $expectedBody = (string) $this->expectedRequest->getBody();
        $body         = (string) $request->getBody();

        if (null === $expectedBody) {
            $this->testCase->assertNull($body);
        } else {
            // Test body lengths before testing full body (looks ugly in readout when it fails)
            $this->testCase->assertEquals(strlen($expectedBody), strlen($body));
            $this->testCase->assertEquals($expectedBody, $body);
        }

        return $this->response;
    }
}
