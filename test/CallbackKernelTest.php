<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\CallbackKernel;
use PHPFastCGI\FastCGIDaemon\Http\Request;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;

/**
 * Tests the callback wrapper.
 */
class CallbackKernelTest extends TestCase
{
    /**
     * Tests an \InvalidArgumentException is thrown when it isn't constructed
     * with a callable.
     */
    public function testInvalidArgumentException()
    {
        $this->expectException(\TypeError::class);
        new CallbackKernel('not a callable function');
    }

    /**
     * Tests that requests are passed through the wrapper correctly.
     */
    public function testHandleRequest()
    {
        $stream = fopen('php://memory', 'r');

        $expectedRequest  = new Request([], $stream);
        $expectedResponse = new Response();

        $kernel = new CallbackKernel(function (Request $request) use ($expectedRequest, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);

            return $expectedResponse;
        });

        $response = $kernel->handleRequest($expectedRequest);
        $this->assertSame($expectedResponse, $response);

        fclose($stream);
    }
}
