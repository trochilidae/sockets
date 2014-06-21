<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/19
 * Time: 16:38
 */

namespace trochilidae\Sockets\Message;

use trochilidae\Sockets\Message;
use trochilidae\Sockets\Resource;
use trochilidae\Sockets\Transports\Transport;

class MessageBuilder {

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var string
     */
    protected $seen = "";

    /**
     * @var
     */
    protected $message;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    function __construct(Resource $resource)
    {
        $this->resource = $resource;
        $this->transport = $resource->getTransport();
    }

    /**
     * @return \trochilidae\Sockets\Resource $resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return resource
     */
    public function getHandle()
    {
        return $this->resource->getHandle();
    }

    /**
     * @param $message
     */
    public function setMessage($message){
        if(is_string($message)){
            $message = new PlainMessage($message);
        }
        $this->message = $message;
    }

    /**
     * @return Message
     */
    public function getMessage(){
        if(is_null($this->message)){
            $this->message = new PlainMessage();
        }
        return $this->message;
    }

    /**
     * Consumes data from the read buffer and stores it in an internal buffer
     *
     * @param $length
     *
     * @return string
     */
    public function peak($length){
        $seen = strlen($this->seen);
        if($toRead = ($length - $seen) > 0){
            $this->seen .= $this->transport->read($toRead);
        }
        return substr($this->seen, 0, $length);
    }

    /**
     * Cosumes data from the internal buffer if available otherwise consumes data from the read buffer
     *
     * @param $length
     *
     * @return string
     */
    public function read($length){
        $seen = strlen($this->seen);
        if($length <= $seen){
            return substr($this->seen, 0, $length);
        }
        return $this->seen . $this->transport->read($length - $seen);
    }
} 