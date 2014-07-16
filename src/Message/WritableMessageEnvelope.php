<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/13
 * Time: 19:45
 */

namespace trochilidae\Sockets\Message;


use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Protocols\ProtocolGroup;
use trochilidae\Sockets\Resource;

class WritableMessageEnvelope extends MessageEnvelope {

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param Protocol $protocol
     * @param string $message
     * @return \trochilidae\Sockets\Message\WritableMessageEnvelope|static
     */
    public static function make(Resource $resource, Protocol $protocol, $message = "")
    {
        $inst = new self();
        $inst->resource = $resource;
        $inst->protocol = clone $protocol;
        if($inst->protocol instanceof ProtocolGroup){
            $inst->protocol->setIteratorMode(ProtocolGroup::IT_MODE_LIFO);
        }
        if($message !== ""){
            $inst->setMessage($message);
        }
        return $inst;
    }
}