<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 13:55
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use trochilidae\Sockets\StreamReader;
use trochilidae\Sockets\Support\ObjectTrait;
use trochilidae\Sockets\Exceptions\InvalidResourceException;

class Resource
{
    //TODO: Find best returns for if resource is closed or resource manager not assigned

    use ObjectTrait;

    /**
     * Asynchronous Flag (Default)
     */
    const Async = 0x1;

    /**
     * Synchronous Flag
     */
    const Sync = 0x2;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var bool
     */
    protected $isPaused = true;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @var StreamReader
     */
    protected $streamReader;

    /**
     * @param Transport                                       $transport
     * @param                                                 $flags
     *
     * @throws Exceptions\InvalidResourceException
     */
    public function __construct(Transport $transport, $flags = null)
    {
        if ($transport->isClosed()) {
            throw new InvalidResourceException();
        }

        $this->transport = $transport;

        if (($flags & self::Sync) != 0) {
            $transport->setBlocking();
        } else {
            $transport->setNonBlocking();
        }
    }

    /**
     * @param ResourceManager $resourceManager
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        //TODO: Think about messages already in buffer requiring specific protocols
        if (!is_null($this->resourceManager)) {
            $this->resourceManager->detach($this);
        }
        $this->resourceManager = $resourceManager;
        $resourceManager->attach($this);
    }

    /**
     * @param Buffer $buffer
     */
    public function setBuffer(Buffer $buffer)
    {
        $handle   = $this->getHandle();
        $messages = [];
        if (!is_null($this->buffer)) {
            $messages = $this->buffer->pull($handle);
        }
        $this->buffer = $buffer;
        $buffer->set($handle, $messages);
    }

    /**
     * @param StreamReader $streamReader
     */
    public function setStreamReader(StreamReader $streamReader)
    {
        $this->streamReader = $streamReader;
    }

    public function getStreamReader()
    {
        if(is_null($this->streamReader)){
            $this->streamReader = new StreamReader($this);
        }
        return $this->streamReader;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * @return \trochilidae\Sockets\Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return resource
     */
    public function getHandle()
    {
        return $this->transport->getHandle();
    }

    /**
     * @return bool
     */
    public function isSync()
    {
        return $this->transport->isSync();
    }

    public function isReadable(){
        return $this->transport->isReadable();
    }

    public function isWritable(){
        return $this->transport->isWritable();
    }

    /**
     * @return bool
     */
    public function isPaused()
    {
        return $this->isPaused;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return $this->transport->isClosed();
    }

    /**
     * @return bool
     */
    public function isEnd()
    {
        return $this->transport->isEnd();
    }

    /**
     * Pause read and write on the resource
     * @return void
     */
    public function pause()
    {
        if ($this->resourceManager) {
            $this->resourceManager->pause($this);
        }
        $this->isPaused = true;
    }

    /**
     * Resume read and write on the resource
     */
    public function resume()
    {
        if ($this->resourceManager) {
            $this->resourceManager->resume($this);
        }
        $this->isPaused = false;
    }

    /**
     * @return bool|string
     */
    public function read()
    {
        if ($this->resourceManager && $this->isReadable() && !$this->isClosed() && $this->isSync()) {
            return $this->resourceManager->read($this);
        }

        return false;
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function write($message)
    {
        //TODO: handle buffer not defined
        if ($this->resourceManager && !$this->isClosed() && !$this->isPaused()) {
            return $this->resourceManager->write($this, $message);
        }

        return false;
    }

    public function close(){
        return $this->resourceManager->close($this);
    }

    function __isset($name)
    {
        return isset($this->storage[$name]);
    }

    function __unset($name)
    {
        unset($this->storage[$name]);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->storage[$name])) {
            return $this->storage[$name];
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->storage[$name] = $value;
    }

}