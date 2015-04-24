<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\Test\FastCGIDaemon\ProtocolConstants;

class ConnectionPoolSimulationFactory
{
    private $input;

    public function __construct()
    {
        $this->input  = '';
    }

    public function createConnectionPoolSimulation()
    {
        return new ConnectionPoolSimulation($this->input);
    }

    public function addHeader($type, $requestId, $contentLength, $paddingLength)
    {
        $this->input .= pack('CCnnCx', ProtocolConstants::FCGI_VERSION_1, $type,
            $requestId, $contentLength, $paddingLength);
    }

    public function addContent($content)
    {
        $this->input .= $content;
    }

    public function addPadding($paddingLength)
    {
        if ($paddingLength > 0) {
            $this->input .= pack('x' . $paddingLength);
        }
    }

    public function addBeginRequestRecord($requestId, $role, $flags)
    {
        $this->addHeader(ProtocolConstants::FCGI_BEGIN_REQUEST, $requestId, 8,
            0);

        $this->input .= pack('nCx5', $role, $flags);
    }

    public function addParamRecord($requestId, $name, $value)
    {
        $content = '';

        $addLength = function ($parameter) use (&$content) {
            $parameterLength = strlen($parameter);

            if ($parameterLength > 0x7F) {
                $content .= pack('N', $parameterLength | 0x80000000);
            } else {
                $content .= pack('C', $parameterLength);
            }
        };

        $addLength($name);
        $addLength($value);

        $content .= $name;
        $content .= $value;

        $this->addHeader(ProtocolConstants::FCGI_PARAMS, $requestId,
            strlen($content), 0);

        $this->input .= $content;
    }

    public function addStdinRecord($requestId, $content, $paddingLength = 0)
    {
        $this->addHeader(ProtocolConstants::FCGI_STDIN, $requestId,
            strlen($content), $paddingLength);

        $this->addContent($content);
        $this->addPadding($paddingLength);
    }
}
