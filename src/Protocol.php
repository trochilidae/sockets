<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 17:17
 */

namespace trochilidae\Sockets;

use React\EventLoop\LoopInterface;
use trochilidae\Sockets\Message\MessageBuilder;

abstract class Protocol
{
    protected $requiresHandshake = false;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    abstract function onOpen(Resource $resource);

    /**
     * @param StreamReader                  $reader
     * @param MessageEnvelope               $message
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return mixed
     */
    abstract function onRead(StreamReader $reader, MessageEnvelope $message, Resource $resource);

    /**
     * @param MessageEnvelope               $message
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return mixed
     */
    abstract function onWrite(MessageEnvelope $message, Resource $resource);

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return mixed
     */
    abstract function onClose(Resource $resource);

    /**
     * @return bool
     */
    public function requiresHandshake(){
        return $this->requiresHandshake;
    }

    /**
     * @param \trochilidae\Sockets\Resource          $resource
     * @param                                        $message
     *
     * @return bool
     */
    protected function write(Resource $resource, $message)
    {
        $messageEnvelope = MessageEnvelope::make($resource, $message, $this);
        $messageEnvelope->getProtocols()->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO);

        return $resource->write($messageEnvelope);
    }

} 