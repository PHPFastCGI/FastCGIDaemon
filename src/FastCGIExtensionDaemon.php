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
    const BUFFER_SIZE = 20480; // 20 KB

    /**
     * @var FastCGIApplicationInterface
     */
    private $fastCGIApplication;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Constructor.
     * 
     * @param FastCGIApplicationInterface $fastCGIApplication
     * @param KernelInterface|callable    $kernel
     */
    public function __construct($fastCGIApplication, $kernel)
    {
        if (!extension_loaded('fastcgi')) {
            throw new \RuntimeException('This implementation of DaemonInterface requires the PHPFastCGI php5-fastcgi extension to be installed and enabled');
        }

        if (!$fastCGIApplication instanceof \FastCGIApplicationInterface) {
            throw new \InvalidArgumentException('First argument passed must be instance of FastCGIApplicationInterface');
        }

        $this->fastCGIApplication = $fastCGIApplication;

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
        while ($this->fastCGIApplication->accept()) {
            $params = $this->fastCGIApplication->getParams();
            $stdin  = fopen('php://temp', 'r+');

            while (!$this->fastCGIApplication->stdinEof()) {
                fwrite($stdin, $this->fastCGIApplication->stdinRead(self::BUFFER_SIZE));
            }

            rewind($stdin);

            $request = new Request($params, $stdin);

            $response = $this->kernel->handleRequest($request);

            if ($response instanceof ResponseInterface) {
                $this->writeResponse($response);
            } elseif ($response instanceof HttpFoundationResponse) {
                $this->writeHttpFoundationResponse($response);
            } else {
                throw new \LogicException('Kernel must return a PSR-7 or HttpFoundation response message');
            }
        }
    }

    /**
     * Write a PSR-7 response to the FastCGI application standard output
     * 
     * @param ResponseInterface $response The PSR-7 HTTP response message
     */
    private function writeResponse(ResponseInterface $response)
    {
        $statusCode   = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        $headerData = "Status: {$statusCode} {$reasonPhrase}\r\n";

        foreach ($response->getHeaders() as $name => $values) {
            $headerData .= $name.': '.implode(', ', $values)."\r\n";
        }

        $headerData .= "\r\n";

        $this->fastCGIApplication->stdoutWrite($headerData);

        $responseBody = $response->getBody();
        $responseBody->rewind();

        while (!$responseBody->eof()) {
            $this->fastCGIApplication->stdoutWrite($responseBody->read(self::BUFFER_SIZE));
        }
    }


    /**
     * Write a HttpFoundation response to the FastCGI application standard output
     * 
     * @param HttpFoundationResponse $response The HttpFoundation response message
     */
    private function writeHttpFoundationResponse(HttpFoundationResponse $response)
    {
        $statusCode = $response->getStatusCode();

        $headerData  = "Status: {$statusCode}\r\n";
        $headerData .= $response->headers . "\r\n";

        $this->fastCGIApplication->stdoutWrite($headerData);
        $this->fastCGIApplication->stdoutWrite($response->getContent());
    }
}
