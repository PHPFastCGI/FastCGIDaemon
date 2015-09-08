<?php

namespace PHPFastCGI\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * This implementation of the DaemonInterface relies on the 'fastcgi' extension
 * being present to provide the 'fastcgi_accept' API.
 */
class FastCGIExtensionDaemon implements DaemonInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Constructor.
     * 
     * @param KernelInterface|callable $kernel
     */
    public function __construct($kernel)
    {
        if ($kernel instanceof KernelInterface) {
            $this->kernel = $kernel;
        } else {
            $this->kernel = new CallbackWrapper($kernel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        fastcgi_accept(function (array $params, $stdin) {
            $request = new Request($params, $stdin);

            $response = $this->kernel->handleRequest($request);

            if ($response instanceof ResponseInterface) {
                $stdout = $this->getStdoutFromResponse($response);
            } elseif ($response instanceof HttpFoundationResponse) {
                $stdout = $this->getStdoutFromHttpFoundationResponse($response);
            } else {
                throw new \LogicException('Kernel must return a PSR-7 or HttpFoundation response message');
            }

            return $stdout;
        });
    }

    /**
     * Get the standard output stream from a PSR-7 response
     * 
     * @param ResponseInterface $response The PSR-7 HTTP response message
     */
    private function getStdoutFromResponse(ResponseInterface $response)
    {
        $statusCode   = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        $headerData = "Status: {$statusCode} {$reasonPhrase}\r\n";

        foreach ($response->getHeaders() as $name => $values) {
            $headerData .= $name.': '.implode(', ', $values)."\r\n";
        }

        $headerData .= "\r\n";

        $stdout = fopen('php://temp', 'r+');
        fwrite($stdout, $headerData);

        $responseBody = $response->getBody();
        $responseBody->rewind();

        while (!$responseBody->eof()) {
            fwrite($stdout, $responseBody->read(20 * 1024));
        }

        rewind($stdout);

        return $stdout;
    }


    /**
     * Get the standard output stream from a HttpFoundation response
     * 
     * @param HttpFoundationResponse $response The HttpFoundation response message
     */
    private function getStdoutFromHttpFoundationResponse(HttpFoundationResponse $response)
    {
        $statusCode = $response->getStatusCode();

        $headerData  = "Status: {$statusCode}\r\n";
        $headerData .= $response->headers . "\r\n";

        $stdout = fopen('php://temp', 'r+');
        fwrite($stdout, $headerData);
        fwrite($stdout, $response->getContent());
        
        rewind($stdout);

        return $stdout;
    }
}
