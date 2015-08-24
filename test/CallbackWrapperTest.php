<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
use PHPFastCGI\FastCGIDaemon\Http\Request;
use Zend\Diactoros\Response;

/**
 * Tests the callback wrapper.
 */
class CallbackWrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests an \InvalidArgumentException is thrown when it isn't constructed
     * with a callable.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        new CallbackWrapper('not a callable function');
    }

    /**
     * Tests that requests are passed through the wrapper correctly.
     */
    public function testHandleRequest()
    {
        $stream = fopen('php://memory', 'r');

        $expectedRequest  = new Request([], $stream);
        $expectedResponse = new Response();

        $kernel = new CallbackWrapper(function (Request $request) use ($expectedRequest, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);

            return $expectedResponse;
        });

        $response = $kernel->handleRequest($expectedRequest);
        $this->assertSame($expectedResponse, $response);

        fclose($stream);
    }
}
