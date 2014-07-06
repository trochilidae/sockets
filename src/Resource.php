<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 13:55
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Support\ObjectTrait;

abstract class Resource
{
    use ObjectTrait;

    /**
     * @var bool
     */
    protected $isPaused = true;

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @var Handle
     */
    protected $handle;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param Handle $handle
     */
    public function __construct(Handle $handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return ProtocolList
     */
    public function getProtocols(){
        return $this->resourceManager->getProtocols();
    }

    /**
     * @return Handle
     */
    public function getHandle()
    {
        return $this->handle;
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
        if ($this->resourceManager && $this->isSync()) {
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
        if ($this->resourceManager) {
            return $this->resourceManager->write($this, $message);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function close(){
        return $this->resourceManager->close($this);
    }

    /**
     * @return bool
     */
    public function isSync()
    {
        return $this->handle->isSync();
    }

    /**
     * @return bool
     */
    public function isReadable(){
        return $this->handle->isReadable();
    }

    /**
     * @return bool
     */
    public function isWritable(){
        return $this->handle->isWritable();
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
        return $this->handle->isClosed();
    }

    /**
     * @return bool
     */
    public function isEnd()
    {
        return $this->handle->isEnd();
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->storage[$name]);
    }

    /**
     * @param $name
     */
    public function __unset($name)
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