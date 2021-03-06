<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 21:05
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Buffer\BufferBridge;
use trochilidae\Sockets\Buffer\DefaultBuffer;
use trochilidae\Sockets\Exceptions\InvalidResourceException;

abstract class Handle {

    /**
     * Asynchronous Flag (Default)
     */
    const Async = 0x1;

    /**
     * Synchronous Flag
     */
    const Sync = 0x2;

    /**
     * @var resource $handle
     */
    protected $handle;

    /**
     * @var BufferBridge
     */
    protected $buffer;

    /**
     * @param resource $handle
     * @param null     $flags
     *
     * @throws Exceptions\InvalidResourceException
     */
    public function __construct($handle, $flags = null)
    {
        $this->handle = $handle;

        if ($this->isClosed()) {
            throw new InvalidResourceException();
        }

        if (($flags & self::Sync) != 0) {
            $this->setBlocking();
        } else {
            $this->setNonBlocking();
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function setBuffer(Buffer $buffer)
    {
        $messages = [];
        if (!is_null($this->buffer)) {
            $messages = $this->buffer->pull();
        }
        $this->buffer = new BufferBridge($this, $buffer);
        $this->buffer->set($messages);
    }

    /**
     * @return BufferBridge
     */
    public function getBuffer()
    {
        if (is_null($this->buffer)) {
            $this->setBuffer(new DefaultBuffer());
        }

        return $this->buffer;
    }

    /**
     * @param array $context
     * @return mixed
     */
    public abstract function setContext(array $context = []);

    /**
     * @param $length
     *
     * @return string|bool
     */
    public abstract function read($length);

    /**
     * @param $message
     *
     * @return null|bool|int
     */
    public abstract function write($message);

    /**
     * @param $length
     * @return string
     */
    public abstract function peek($length);

    /**
     * @return bool
     */
    public abstract function close();

    /**
     * @return bool
     */
    public abstract function setBlocking();

    /**
     * @return bool
     */
    public abstract function setNonBlocking();

    /**
     * @return bool
     */
    public abstract function isReadable();

    /**
     * @return bool
     */
    public abstract function isWritable();

    /**
     * @return bool
     */
    public abstract function isClosed();

    /**
     * @return bool
     */
    public abstract function eof();

    /**
     * @return bool
     */
    public abstract function isSync();

    /**
     * @return \React\EventLoop\Stream|resource
     */
    public function get(){
        return $this->handle;
    }

    /**
     * @return int
     */
    public function toInt(){
        return (int)$this->handle;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->handle;
    }

}