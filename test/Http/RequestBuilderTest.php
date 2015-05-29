<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\Http\RequestBuilder;

class RequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuilder()
    {
        $builder = new RequestBuilder();

        $params = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'content_type'   => 'application/x-www-form-urlencoded',
            'REQUEST_URI'    => '/my-page',
            'QUERY_STRING'   => 'bar=foo&world=hello',
            'HTTP_cookie'    => 'one=two; three=four; five=six',
        ];

        $content = 'foo=bar&hello=world';

        foreach ($params as $name => $value) {
            $builder->addParam($name, $value);
        }

        $builder->addStdin($content);

        $request = $builder->getRequest();

        $server = $request->getServerParams();

        foreach ($params as $name => $value) {
            $this->assertEquals($server[strtoupper($name)], $value);
        }

        $this->assertEquals($request->getQueryParams(), ['bar' => 'foo', 'world' => 'hello', ]);

        $this->assertEquals($request->getParsedBody(), ['foo' => 'bar', 'hello' => 'world', ]);

        $this->assertEquals($request->getCookieParams(), ['one' => 'two', 'three' => 'four', 'five' => 'six', ]);
    }
}
