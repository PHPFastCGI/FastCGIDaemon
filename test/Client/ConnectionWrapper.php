<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Client;

use PHPFastCGI\FastCGIDaemon\DaemonInterface;

/**
 * Helper class that wraps a stream resource and provides helper methods for
 * writing and reading FastCGI records.
 */
class ConnectionWrapper
{
    /**
     * @var resource
     */
    protected $stream;

    /**
     * Constructor.
     * 
     * @param resource $stream
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    /**
     * Read a record from the connection.
     * 
     * @param  \PHPUnit_Framework_TestCase $testCase
     * 
     * @return array
     */
    public function readRecord(\PHPUnit_Framework_TestCase $testCase)
    {
        $headerData = fread($this->stream, 8);
        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

        $testCase->assertEquals(8, strlen($headerData));

        $record = unpack($headerFormat, $headerData);

        if ($record['contentLength'] > 0) {
            $record['contentData'] = '';

            do {
                $block = fread($this->stream, $record['contentLength'] - strlen($record['contentData']));

                $record['contentData'] .= $block;
            } while (strlen($block) > 0 && strlen($record['contentData']) !== $record['contentLength']);

            $testCase->assertEquals($record['contentLength'], strlen($record['contentData']));
        } else {
            $record['contentData'] = '';
        }

        if ($record['paddingLength'] > 0) {
            fread($this->stream, $record['paddingLength']);
        }

        return $record;
    }

    /**
     * Write a record to the stream.
     * 
     * @param string $type
     * @param string $requestId
     * @param string $content
     * @param int    $paddingLength
     */
    public function writeRecord($type, $requestId, $content = '', $paddingLength = 0)
    {
        $header  = pack('CCnnCx', DaemonInterface::FCGI_VERSION_1, $type, $requestId, strlen($content), $paddingLength);
        $padding = pack('x' . $paddingLength);

        fwrite($this->stream, $header);
        fwrite($this->stream, $content);
        fwrite($this->stream, $padding);
    }

    /**
     * Write a begin request record.
     * 
     * @param int $requestId
     * @param int $role
     * @param int $flags
     */
    public function writeBeginRequestRecord($requestId, $role, $flags)
    {
        $content = pack('nCx5', $role, $flags);
        $this->writeRecord(DaemonInterface::FCGI_BEGIN_REQUEST, $requestId, $content);
    }

    /**
     * Write a params record.
     * 
     * @param int    $requestId
     * @param string $name
     * @param string $value
     */
    public function writeParamsRecord($requestId, $name = null, $value = null)
    {
        if (null === $name && null === $value) {
            $this->writeRecord(DaemonInterface::FCGI_PARAMS, $requestId);

            return;
        }

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

        $contentLength = strlen($content);
        $paddingLength = ((int) ceil(((float) $contentLength) / 8.0) * 8) - $contentLength;

        $this->writeRecord(DaemonInterface::FCGI_PARAMS, $requestId, $content, $paddingLength);
    }

    /**
     * Write an abort request record.
     * 
     * @param int $requestId
     */
    public function writeAbortRequestRecord($requestId)
    {
        $this->writeRecord(DaemonInterface::FCGI_ABORT_REQUEST, $requestId);
    }

    /**
     * Write a stdin record.
     * 
     * @param int    $requestId
     * @param string $content
     */
    public function writeStdinRecord($requestId, $content = '')
    {
        $this->writeRecord(DaemonInterface::FCGI_STDIN, $requestId, $content);
    }
}
