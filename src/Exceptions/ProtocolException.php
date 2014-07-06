<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/29
 * Time: 16:35
 */

namespace trochilidae\Sockets\Exceptions;


use trochilidae\Sockets\MessageEnvelope;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Resource;

class ProtocolException extends \Exception {

    protected $message;

    function __construct(Resource $resource, $message = "")
    {
        if(!($protocol = get_calling_class()) instanceof Protocol){
            throw new \RuntimeException("");
        }
        $this->message = MessageEnvelope::make($resource, $message, $protocol);
    }
}