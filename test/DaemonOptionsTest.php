<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\DaemonOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the daemon options.
 */
class DaemonOptionsTest extends TestCase
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
        $this->assertEquals(false,         $options->getOption(DaemonOptions::AUTO_SHUTDOWN));
    }

    /**
     * Test that an InvalidArgumentException is thrown when an invalid logger
     * is provided.
     */
    public function testInvalidLogger()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DaemonOptions([DaemonOptions::LOGGER => 'foo']);
    }

    /**
     * Test that an InvalidArgumentException is thrown when the object is
     * constructed with an unknown option.
     */
    public function testUnknownOptionInConstructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DaemonOptions(['hello' => 'world']);
    }

    /**
     * Test that an InvalidArgumentException is thrown when ::getOption is
     * called with an unknown option.
     */
    public function testGetUnknownOption()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DaemonOptions())->getOption('hello');
    }
}
