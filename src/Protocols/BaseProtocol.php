<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 17:17
 */

namespace trochilidae\Sockets\Protocols;

use trochilidae\Sockets\Message\MessageBuilder;
use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Resource;

abstract class BaseProtocol implements Protocol
{
    protected $name;

    protected $requiresHandshake = false;

    function __construct()
    {
        if(is_null($this->name)){
            $this->name = get_real_class($this);
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    abstract function onOpen(Resource $resource);

    /**
     * @param ReadableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    abstract function onRead(ReadableMessageEnvelope $message, Resource $resource);

    /**
     * @param WritableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    abstract function onWrite(WritableMessageEnvelope $message, Resource $resource);

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

} 