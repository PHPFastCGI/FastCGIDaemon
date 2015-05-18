<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\Http\RequestEnvironmentBuilder;

class RequestEnvironmentBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuilder()
    {
        $builder = new RequestEnvironmentBuilder();

        $params = [
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

        $requestEnvironment = $builder->getRequestEnvironment();

        $server = $requestEnvironment->getServer();

        foreach ($params as $name => $value) {
            $this->assertEquals($server[strtoupper($name)], $value);
        }

        $this->assertEquals($requestEnvironment->getQuery(), ['bar' => 'foo',
                'world' => 'hello', ]);

        $this->assertEquals($requestEnvironment->getPost(), ['foo' => 'bar',
                'hello' => 'world', ]);

        $this->assertEquals($requestEnvironment->getCookies(), ['one' => 'two',
                'three' => 'four', 'five' => 'six', ]);
    }
}
