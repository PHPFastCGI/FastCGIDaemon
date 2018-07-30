<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Http;

use PHPFastCGI\FastCGIDaemon\Http\Request;

/**
 * Test that the request builder is correctly building the PSR-7 request
 * message.
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the request builder is correctly building the request messages.
     */
    public function testRequest()
    {
        $expectedQuery   = ['bar' => 'foo', 'world' => 'hello'];
        $expectedPost    = ['foo' => 'bar', 'hello' => 'world'];
        $expectedCookies = ['one' => 'two', 'three' => 'four', 'five' => 'six'];

        // Set up FastCGI params and content
        $params = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => 'POST',
            'content_type'    => 'application/x-www-form-urlencoded',
            'REQUEST_URI'     => '/my-page',
            'QUERY_STRING'    => 'bar=foo&world=hello',
            'HTTP_cookie'     => 'one=two; three=four; five=six',
        ];

        // Set up the FastCGI stdin data stream resource
        $content = 'foo=bar&hello=world';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);

        // Create the request
        $request = new Request($params, $stream);

        // Check request object
        $this->assertEquals($expectedQuery,   $request->getQuery());
        $this->assertEquals($expectedPost,    $request->getPost());
        $this->assertEquals($expectedCookies, $request->getCookies());
        $this->assertEquals($stream,          $request->getStdin());

        // Check the PSR server request
        rewind($stream);
        $serverRequest = $request->getServerRequest();
        $this->assertEquals($params['REQUEST_URI'], $serverRequest->getUri()->getPath());
        $this->assertEquals($expectedQuery,         $serverRequest->getQueryParams());
        $this->assertEquals($expectedPost,          $serverRequest->getParsedBody());
        $this->assertEquals($expectedCookies,       $serverRequest->getCookieParams());
        $this->assertEquals($content,      (string) $serverRequest->getBody());

        // Check the HttpFoundation request
        rewind($stream);
        $httpFoundationRequest = $request->getHttpFoundationRequest();
        $this->assertEquals($params['REQUEST_URI'], $httpFoundationRequest->getRequestUri());
        $this->assertEquals($expectedQuery,         $httpFoundationRequest->query->all());
        $this->assertEquals($expectedPost,          $httpFoundationRequest->request->all());
        $this->assertEquals($expectedCookies,       $httpFoundationRequest->cookies->all());
        $this->assertEquals($content,               $httpFoundationRequest->getContent());
    }

    public function testMultipartContent()
    {
        $expectedPost    = ['foo' => 'A normal stream', 'baz' => 'string'];

        // Set up FastCGI params and content
        $params = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => 'POST',
            'content_type'    => 'multipart/form-data; boundary="578de3b0e3c46.2334ba3"',
            'REQUEST_URI'     => '/my-page',
        ];

        // Set up the FastCGI stdin data stream resource
        $content = <<<HTTP
--578de3b0e3c46.2334ba3
Content-Disposition: form-data; name="foo"
Content-Length: 15

A normal stream
--578de3b0e3c46.2334ba3
Content-Disposition: form-data; name="bar"; filename="bar.png"
Content-Length: 71
Content-Type: image/png

?PNG

???
IHDR??? ??? ?????? ???? IDATxc???51?)?:??????IEND?B`?
--578de3b0e3c46.2334ba3
Content-Type: text/plain
Content-Disposition: form-data; name="baz"
Content-Length: 6

string
--578de3b0e3c46.2334ba3--
HTTP;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);

        // Create the request
        $request = new Request($params, $stream);

        // Check request object
        $this->assertEquals($expectedPost,    $request->getPost());
        $this->assertEquals($stream,          $request->getStdin());

        // Check the PSR server request
        rewind($stream);
        $serverRequest = $request->getServerRequest();
        $this->assertEquals($expectedPost, $serverRequest->getParsedBody());
        $this->assertCount(1,              $serverRequest->getUploadedFiles());
        $this->assertEquals($content,      $serverRequest->getBody()->__toString());

        // Check the HttpFoundation request
        rewind($stream);
        $httpFoundationRequest = $request->getHttpFoundationRequest();
        $this->assertEquals($expectedPost, $httpFoundationRequest->request->all());
        $this->assertCount(1,              $httpFoundationRequest->files->all());
        $this->assertEquals($content,      $httpFoundationRequest->getContent());
    }
}
