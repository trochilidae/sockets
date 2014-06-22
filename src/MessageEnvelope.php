<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/22
 * Time: 16:45
 */

namespace trochilidae\Sockets;


use trochilidae\Sockets\Message\PlainMessage;

class MessageEnvelope implements Message {

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var \SplDoublyLinkedList
     */
    protected $protocols;

    /**
     * @var Resource
     */
    protected $resource;

    function __construct(Resource $resource, \SplDoublyLinkedList $protocols)
    {
        $this->protocols = $protocols;
        $this->resource = $resource;
    }

    public function getResource(){
        return $this->resource;
    }

    /**
     * @return \SplDoublyLinkedList
     */
    public function getProtocols(){
        return $this->protocols;
    }

    /**
     * @param Message $message
     */
    public function setMessage(Message $message){
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

    function __toString()
    {
        return (string)$this->message;
    }
}