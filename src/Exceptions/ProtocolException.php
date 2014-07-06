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
        if(!($protocol = $this->getCallingClass()) instanceof Protocol){
            throw new \RuntimeException("");
        }
        $this->message = MessageEnvelope::make($resource, $protocol, $message);
    }

    protected function getCallingClass(){
        //get the trace
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ( $i=1; $i< count($trace); $i++ ) {
            if ( isset( $trace[$i] ) ) // is it set?
                if ( $class != $trace[$i]['class'] ){
                    return $trace[$i]['object'];
                } // is it a different class
        }
    }
}