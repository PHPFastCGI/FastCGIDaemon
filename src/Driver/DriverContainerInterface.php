<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon\Driver;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;

interface DriverContainerInterface
{
    /**
     * Obtain a daemon factory object for the specified driver.
     *
     * @param string $driver The name of the driver
     *
     * @return DaemonFactoryInterface The daemon factory for the driver
     */
    public function getFactory(string $driver): DaemonFactoryInterface;
}
