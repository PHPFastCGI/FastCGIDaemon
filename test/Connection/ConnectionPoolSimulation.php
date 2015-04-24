<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Connection;

use PHPFastCGI\FastCGIDaemon\Connection\ConnectionPoolInterface;

class ConnectionPoolSimulation implements ConnectionPoolInterface
{
    private $input;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function accept()
    {
        return new ConnectionSimulation($this->input);
    }

    public function close()
    {
    }
}
