<?php

namespace trochilidae\Sockets\Protocols;

use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Resource;
use trochilidae\Sockets\Support\ArrayIterator;

abstract class ProtocolGroup extends ArrayIterator implements Protocol
{

    protected $iteratorVariable = "protocols";

    protected $name;

    protected $protocols = [];

    protected $pendingMessages = [];

    function __construct()
    {
        if(is_null($this->name)){
            $this->name = get_real_class($this);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setIteratorMode($mode){
        $this->iteratorMode = $mode;
        $this->rewind();
        return $this;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    public function onOpen(Resource $resource)
    {
        $this->iteratorMode = ArrayIterator::IT_MODE_FIFO;
        foreach($this as $protocol){
            /**
             * @var BaseProtocol $protocol
             */
            $protocol->onOpen($resource);
        }
    }

    /**
     * @param ReadableMessageEnvelope $messageEnvelope
     * @param \trochilidae\Sockets\Resource $resource
     * @throws \trochilidae\Sockets\Exceptions\InvalidArgumentException
     * @return bool
     */
    public function onRead(ReadableMessageEnvelope $messageEnvelope, Resource $resource)
    {
        $protocolGroup = $messageEnvelope->getProtocol();
        if(!$protocolGroup instanceof self){
            throw new InvalidArgumentException('Invalid $messageEnvelope. Protocol is not an instance of ' . __CLASS__);
        }

        while ($protocolGroup->valid()) {
            /**
             * @var BaseProtocol $protocol
             */
            $protocol = $protocolGroup->current();
            $response = $protocol->onRead($messageEnvelope, $resource);
            if ($response === false) {
                return false;
            }else if (is_string($response) || $response instanceof Message) {
                $messageEnvelope->setMessage($response);
            }
            $protocolGroup->next();
        }

        return $response;
    }

    /**
     * @param WritableMessageEnvelope $messageEnvelope
     * @param \trochilidae\Sockets\Resource $resource
     * @throws \trochilidae\Sockets\Exceptions\InvalidArgumentException
     * @return bool
     */
    public function onWrite(WritableMessageEnvelope $messageEnvelope, Resource $resource)
    {
        $protocolGroup = $messageEnvelope->getProtocol();
        if(!$protocolGroup instanceof self){
            throw new InvalidArgumentException('Invalid $messageEnvelope. Protocol is not an instance of ' . __CLASS__);
        }
        while ($protocolGroup->valid()) {
            /**
             * @var BaseProtocol $protocol
             */
            $protocol = $protocolGroup->current();
            $response = $protocol->onWrite($messageEnvelope, $resource);
            if ($response === false) {
                return false;
            }
            else if (is_string($response) || $response instanceof Message) {
                $messageEnvelope->setMessage($response);
            }
            $protocolGroup->next();
        }
        return $response;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return mixed
     */
    public function onClose(Resource $resource)
    {
        $this->iteratorMode = ArrayIterator::IT_MODE_LIFO;
        foreach($this as $protocol){
            /**
             * @var BaseProtocol $protocol
             */
            $protocol->onClose($resource);
        }
    }

    public function __toString()
    {
        return $this->name;
    }
}