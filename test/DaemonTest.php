<?php

namespace PHPFastCGI\Test\FastCGIDaemon;

use PHPFastCGI\FastCGIDaemon\ConnectionHandler\SingleplexedResponderConnectionHandlerFactory;
use PHPFastCGI\FastCGIDaemon\SingleplexedDaemon;
use PHPFastCGI\Test\FastCGIDaemon\Connection\ConnectionPoolSimulationFactory;
use PHPFastCGI\Test\FastCGIDaemon\ProtocolConstants;

class DaemonTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalRequest()
    {
        // Build the data to be transmitted
        $requestId = rand(1, 254);

        $parameters = array(
            'param'                => 'value',
            'this Parameter'       => 'this Value',
            hash('sha512', rand()) => 'long name test',
            'long value test'      => hash('sha512', rand()),
            hash('sha512', rand()) => hash('sha512', rand())
        );

        $stdinPackets = array(hash('sha512', rand()), 'other test');

        $stdinJoined = '';

        foreach ($stdinPackets as $stdinPacket) {
            $stdinJoined .= $stdinPacket;
        }

        // Build the simulator
        $connectionPoolFactory = new ConnectionPoolSimulationFactory();
        $connectionPoolFactory->addBeginRequestRecord($requestId,
            ProtocolConstants::FCGI_RESPONDER, 0);

        foreach ($parameters as $name => $value) {
            $connectionPoolFactory->addParamRecord($requestId, $name, $value);
        }
        $connectionPoolFactory->addHeader(ProtocolConstants::FCGI_PARAMS,
            $requestId, 0, 0);

        foreach ($stdinPackets as $stdinPacket) {
            $connectionPoolFactory->addStdinRecord($requestId, $stdinPacket, 8);
        }
        $connectionPoolFactory->addHeader(ProtocolConstants::FCGI_STDIN,
            $requestId, 0, 0);

        $connectionPool = $connectionPoolFactory->createConnectionPoolSimulation();

        // Simulate the daemon
        $daemon = new SingleplexedDaemon($connectionPool,
            new SingleplexedResponderConnectionHandlerFactory());

        $request = $daemon->getRequest();

        $this->assertSame($request->getServer(),    $parameters);
        $this->assertSame($request->getContent(),   $stdinJoined);
        $this->assertSame($request->getRequestId(), $requestId);
    }
}
