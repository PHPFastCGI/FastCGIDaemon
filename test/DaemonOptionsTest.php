<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use Psr\Log\NullLogger;

/**
 * Tests the daemon options.
 */
class DaemonOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the daemon options object works properly.
     */
    public function testDaemonOptions()
    {
        $logger = new NullLogger();
        $requestLimit = 10;
        $memoryLimit  = 11;
        $timeLimit    = 12;

        $options = new DaemonOptions($logger, $requestLimit, $memoryLimit, $timeLimit);

        $this->assertSame($logger, $options->getLogger());
        $this->assertEquals($requestLimit, $options->getRequestLimit());
        $this->assertEquals($memoryLimit, $options->getMemoryLimit());
        $this->assertEquals($timeLimit, $options->getTimeLimit());
    }
}
