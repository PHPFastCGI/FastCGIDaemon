<?php

namespace PHPFastCGI\Test\FastCGIDaemon\Driver\Userland;

use PHPFastCGI\FastCGIDaemon\Driver\FastCGIExtension\FastCGIExtensionDaemon;
use PHPFastCGI\Test\FastCGIDaemon\Driver\AbstractDaemonTestCase;

/**
 * Tests the daemon.
 */
class FastCGIExtensionDaemonTest extends AbstractDaemonTestCase
{
    protected function createDaemon(array $context)
    {
        if (!extension_loaded('fastcgi')) {
            $this->markTestIncomplete('FastCGI extension must be installed to perform this test');
        }

        $prefix = 'tcp://localhost';

        if (strncmp($context['address'], $prefix, strlen($prefix)) !== 0) {
            $this->markTestIncomplete('Can only perform this test listening on localhost');
        }

        $fastCGIApplication = new \FastCGIApplication(substr($context['address'], strlen($prefix)));
        return new FastCGIExtensionDaemon($context['kernel'], $context['options'], $fastCGIApplication);
    }
}
