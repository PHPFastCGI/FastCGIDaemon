<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionInterface;

class ConnectionSimulation implements ConnectionInterface
{
    private $input;
    private $inputLength;
    private $position;

    public function __construct($input)
    {
        $this->input       = $input;
        $this->inputLength = strlen($input);
        $this->position    = 0;
    }

    public function read($length)
    {
        $lengthAvailable = $this->inputLength - $this->position;
        $lengthRead      = min($lengthAvailable, $length);

        $data = substr($this->input, $this->position, $lengthRead);

        $this->position += $length;

        return $data;
    }

    public function write($buffer)
    {
    }

    public function close()
    {
    }
}
