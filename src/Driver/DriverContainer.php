<?php

namespace PHPFastCGI\FastCGIDaemon\Driver;

class DriverContainer implements DriverContainerInterface
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
    public function getFactory($driver)
    {
        if (!isset($this->drivers[$driver])) {
            throw new \InvalidArgumentException('Unknown driver: '.$driver);
        }

        if (null === $this->drivers[$driver]['object']) {
            $this->drivers[$driver]['object'] = new $this->drivers[$driver]['classPath']();
        }

        return $this->drivers[$driver]['object'];
    }
}
