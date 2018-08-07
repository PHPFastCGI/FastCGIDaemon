<?php

declare(strict_types=1);

namespace PHPFastCGI\FastCGIDaemon\Driver;

use PHPFastCGI\FastCGIDaemon\DaemonFactoryInterface;

final class DriverContainer implements DriverContainerInterface
{
    /**
     * @var array
     */
    private $drivers;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->drivers = [
            'userland' => [
                'object'    => null,
                'classPath' => 'PHPFastCGI\FastCGIDaemon\Driver\Userland\UserlandDaemonFactory',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFactory(string $driver): DaemonFactoryInterface
    {
        if (!isset($this->drivers[$driver])) {
            throw new \InvalidArgumentException('Unknown driver: '.$driver);
        }

        if (null === $this->drivers[$driver]['object']) {
            $class = $this->drivers[$driver]['classPath'];
            $this->drivers[$driver]['object'] = new $class();
        }

        return $this->drivers[$driver]['object'];
    }
}
