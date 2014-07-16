<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/13
 * Time: 19:45
 */

namespace trochilidae\Sockets\Message;


use trochilidae\Sockets\Handle;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Protocols\ProtocolGroup;
use trochilidae\Sockets\Resource;

class ReadableMessageEnvelope extends MessageEnvelope {

    /**
     * @var Handle
     */
    protected $handle;

    /**
     * @var string
     */
    protected $seen = "";

    /**
     * @var
     */
    protected $message;

    /**
     * @param \trochilidae\Sockets\Handle $handle
     */
    function __construct(Handle $handle)
    {
        $this->handle = $handle;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param Protocol $protocol
     * @param string $message
     * @return \trochilidae\Sockets\Message\ReadableMessageEnvelope|static
     */
    public static function make(Resource $resource, Protocol $protocol, $message = "")
    {
        $inst = new self($resource->getHandle());
        $inst->resource = $resource;
        $inst->protocol = clone $protocol;
        if($inst->protocol instanceof ProtocolGroup){
            $inst->protocol->setIteratorMode(ProtocolGroup::IT_MODE_FIFO);
        }
        if($message !== ""){
            $inst->setMessage($message);
        }
        return $inst;
    }

    /**
     * @param $length
     * @return string
     */
    public function peek($length){
        return $this->handle->peek($length);
    }

    /**
     * Consumes data from the read buffer and stores it in an internal buffer
     *
     * @param $length
     *
     * @return string
     */
    public function readAndStore($length){
        $seen = strlen($this->seen);
        if(($toRead = $length - $seen) > 0){
            $this->seen .= $this->handle->read($toRead);
        }
        return substr($this->seen, 0, $length);
    }

    /**
     * Consumes data from the internal buffer if available otherwise consumes data from the read buffer
     *
     * @param $length
     *
     * @return string
     */
    public function read($length = false){
        if($length === false){
            return $this->seen;
        }
        $seen = strlen($this->seen);
        if($length <= $seen){
            $str = substr($this->seen, 0, $length);
            $this->seen = substr($this->seen, $length);
            return $str;
        }
        $str = $this->seen . $this->handle->read($length - $seen);
        $this->seen = "";
        return $str;
    }

    /**
     * Save the data in the internal buffer as a message
     */
    public function save()
    {
        $this->setMessage($this->message . $this->seen);
        return $this->message;
    }
} 