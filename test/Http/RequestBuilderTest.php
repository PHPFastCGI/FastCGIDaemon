<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\Http\RequestBuilder;

/**
 * Test that the request builder is correctly building the PSR-7 request
 * message.
 */
class RequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the request builder is correctly building the PSR-7 request
     * message.
     */
    public function testBuilder()
    {
        // Create a builder
        $builder = new RequestBuilder();

        // Set up FastCGI params and content
        $params = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'content_type'   => 'application/x-www-form-urlencoded',
            'REQUEST_URI'    => '/my-page',
            'QUERY_STRING'   => 'bar=foo&world=hello',
            'HTTP_cookie'    => 'one=two; three=four; five=six',
        ];

        $content = 'foo=bar&hello=world';

        // Add to them to the builder
        foreach ($params as $name => $value) {
            $builder->addParam($name, $value);
        }

        $builder->addStdin($content);

        // Get the request
        $request = $builder->getRequest();

        // Check request object
        $builtServerParams = $request->getServerParams();
        foreach ($params as $name => $value) {
            $this->assertEquals($builtServerParams[strtoupper($name)], $value);
        }

        $this->assertEquals($request->getQueryParams(), ['bar' => 'foo', 'world' => 'hello', ]);

        $this->assertEquals($request->getParsedBody(), ['foo' => 'bar', 'hello' => 'world', ]);

        $this->assertEquals($request->getCookieParams(), ['one' => 'two', 'three' => 'four', 'five' => 'six', ]);
    }
}
