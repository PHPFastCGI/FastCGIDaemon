<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\CallbackWrapper;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

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
        $expectedRequest  = new ServerRequest();
        $expectedResponse = new Response();

        $kernel = new CallbackWrapper(function (ServerRequest $request) use ($expectedRequest, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);
            return $expectedResponse;
        });

        $response = $kernel->handleRequest($expectedRequest);
        $this->assertSame($expectedResponse, $response);
    }
}
