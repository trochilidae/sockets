<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/06
 * Time: 15:49
 */

namespace trochilidae\Sockets\Buffer;


use trochilidae\Sockets\Buffer;
use trochilidae\Sockets\Handle;

class BufferBridge {

    /**
     * @var Handle
     */
    protected $handle;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * @param Handle $handle
     * @param Buffer $buffer
     */
    function __construct(Handle $handle, Buffer $buffer)
    {
        $this->handle = $handle;
        $this->buffer = $buffer;
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function unshift($message){
        return $this->buffer->unshift($this->handle, $message);
    }

    /**
     * @return int
     */
    public function getSize(){
        return $this->buffer->getSize();
    }

    /**
     * @return mixed|null
     */
    public function shift(){
        return $this->buffer->shift($this->handle);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function write($message){
        return $this->buffer->write($this->handle, $message);
    }

    /**
     * @return array
     */
    public function pull(){
        return $this->buffer->pull($this->handle);
    }

    /**
     * @param array $messages
     */
    public function set(array $messages = []){
        $this->buffer->set($this->handle, $messages);
    }

    /**
     * @return bool
     */
    public function isEmpty(){
        return $this->buffer->isEmpty($this->handle);
    }
}