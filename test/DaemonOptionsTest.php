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
        $logger       = new NullLogger();
        $requestLimit = 10;
        $memoryLimit  = $timeLimit = DaemonOptions::NO_LIMIT; // implicit as not passed in options array

        $options = new DaemonOptions([
            DaemonOptions::LOGGER        => $logger,
            DaemonOptions::REQUEST_LIMIT => $requestLimit,
        ]);

        $this->assertSame($logger, $options->getOption(DaemonOptions::LOGGER));

        $this->assertEquals($requestLimit, $options->getOption(DaemonOptions::REQUEST_LIMIT));
        $this->assertEquals($memoryLimit,  $options->getOption(DaemonOptions::MEMORY_LIMIT));
        $this->assertEquals($timeLimit,    $options->getOption(DaemonOptions::TIME_LIMIT));
    }

    /**
     * Test that an InvalidArgumentException is thrown when the object is
     * constructed with an unknown option.
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testUnknownOptionInConstructor()
    {
        new DaemonOptions(['hello' => 'world']);
    }

    /**
     * Test that an InvalidArgumentException is thrown when ::getOption is
     * called with an unknown option.
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testGetUnknownOption()
    {
        (new DaemonOptions())->getOption('hello');
    }
}
