<?php

namespace PHPFastCGI\FastCGIDaemon\ConnectionHandler;

use PHPFastCGI\FastCGIDaemon\Exception\AbstractRequestException;
use PHPFastCGI\FastCGIDaemon\Exception\ConnectionException;
use PHPFastCGI\FastCGIDaemon\Exception\MultiplexingUnsupportedException;
use PHPFastCGI\FastCGIDaemon\Exception\ProtocolException;
use PHPFastCGI\FastCGIDaemon\Exception\UnknownRoleException;
use PHPFastCGI\FastCGIDaemon\Http\Request;
use PHPFastCGI\FastCGIDaemon\Record\BeginRequestRecord;
use PHPFastCGI\FastCGIDaemon\Record\EndRequestRecord;
use PHPFastCGI\FastCGIDaemon\Record\ParamRecord;
use PHPFastCGI\FastCGIDaemon\Record\Record;
use PHPFastCGI\FastCGIDaemon\Record\StdinRecord;
use PHPFastCGI\FastCGIDaemon\Record\StdoutRecord;

class SingleplexedResponderConnectionHandler implements
    SingleplexedConnectionHandlerInterface
{
    use ConnectionHandlerTrait;

    const STATE_READY_FOR_REQUEST  = 0;
    const STATE_READY_FOR_RESPONSE = 1;
    const STATE_DEAD               = 2;

    protected $state              = self::STATE_READY_FOR_REQUEST;
    protected $beginRequestRecord = null;
    protected $requestId          = null;
    protected $stdinRecords       = array();
    protected $paramRecords       = array();
    protected $gotLastStdinRecord = false;
    protected $gotLastParamRecord = false;

    protected function ready()
    {
        return $this->gotLastStdinRecord && $this->gotLastParamRecord;
    }

    protected function createRequest()
    {
        $server = array();

        foreach ($this->paramRecords as $record) {
            $server[$record->getName()] = $record->getValue();
        }

        $this->paramRecords = null; // clear up memory

        $content = '';

        foreach ($this->stdinRecords as $record) {
            $content .= $record->getContent();
        }

        $this->stdinRecords = null; // clear up memory

        $this->state = self::STATE_READY_FOR_RESPONSE;

        return new Request($this, $this->requestId, $server, $content);
    }

    protected function readBeginRequestRecord()
    {
        $this->beginRequestRecord = Record::readFromConnection(
            $this->connection);

        if (!$this->beginRequestRecord instanceof BeginRequestRecord) {
            throw new ProtocolException('Expected begin request record');
        }

        $this->requestId = $this->beginRequestRecord->getHeader()
            ->getRequestId();

        if ($this->beginRequestRecord->keepConnection()) {
            $exception = new MultiplexingUnsupportedException;
            $exception->setRequestId($this->requestId);
            throw $exception;
        }

        if ($this->beginRequestRecord->getRole() !==
            BeginRequestRecord::FCGI_RESPONDER) {
            $exception = new UnknownRoleException;
            $exception->setRequestId($this->requestId);
            throw $exception;
        }
    }

    protected function addParamRecord(ParamRecord $record)
    {
        if ($record->isEndRecord()) {
            $this->gotLastParamRecord = true;
        } else {
            $this->paramRecords[] = $record;
        }
    }

    protected function addStdinRecord(StdinRecord $record)
    {
        if ($record->isEndRecord()) {
            $this->gotLastStdinRecord = true;
        } else {
            $this->stdinRecords[] = $record;
        }
    }

    protected function readNextRecord()
    {
        $record = Record::readFromConnection($this->connection);

        $this->validateRequestId($record, $this->requestId);

        if ($record instanceof ParamRecord) {
            $this->addParamRecord($record);
        } elseif ($record instanceof StdinRecord) {
            $this->addStdinRecord($record);
        } else {
            throw new ProtocolException(
                'Unrecognised record for responder role');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        if ($this->state !== self::STATE_READY_FOR_REQUEST) {
            throw new \LogicException('Connection handler not ready');
        }

        try {
            $this->readBeginRequestRecord();

            do {
                $record = $this->readNextRecord();
            } while (!$this->ready());

            return $this->createRequest();
        } catch (ProtocolException $protocolException) {
            if ($protocolException instanceof AbstractRequestException) {
                $requestId      = $protocolException->getRequestId();
                $protocolStatus = $protocolException->getProtocolStatus();
                $record = new EndRequestRecord($requestId, 0, $protocolStatus);
                $record->writeToConnection($this->connection);
            }

            $this->connection->close();
        } catch (ConnectionException $connectionException) { }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function sendResponse(Request $request, $response)
    {
        if ($this->state !== self::STATE_READY_FOR_RESPONSE) {
            throw new \LogicException('Connection handler not ready');
        } elseif ($this->requestId !== $request->getRequestId()) {
            throw new \LogicException('Invalid connection handler for request');
        }

        $chunks = str_split($response, 65535);

        try {
            foreach ($chunks as $chunk) {
                $stdoutRecord = new StdoutRecord($this->requestId, $chunk);
                $stdoutRecord->writeToConnection($this->connection);
            }

            $stdoutRecord = new StdoutRecord($this->requestId, null);
            $stdoutRecord->writeToConnection($this->connection);

            $endRequestRecord = new EndRequestRecord($this->requestId, 0,
                EndRequestRecord::FCGI_REQUEST_COMPLETE);
            $endRequestRecord->writeToConnection($this->connection);

            $this->connection->close();
            $this->connection = null;

            $this->state = self::STATE_DEAD;
        } catch (ConnectionException $exception) { }
    }
}
