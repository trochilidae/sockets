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
     * @return Handle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    public function getStatus()
    {
        if($this->isClosed()){
            return ResourceStatusStore::STATUS_CLOSED;
        }else if(is_null($this->resourceManager)){
            return ResourceStatusStore::STATUS_DISCONNECTED;
        }
        return $this->resourceManager->getStatus($this);
    }

    /**
     * @return string
     */
    public function protocol()
    {
        return $this->_getResourceManager()->protocol();
    }

    /**
     * Pause read and write on the resource
     * @return void
     */
    public function pause()
    {
        $this->_getResourceManager()->pause($this);
    }

    /**
     * Pause read and write on the resource
     * @return void
     */
    public function pauseRead()
    {
        $this->_getResourceManager()->pauseRead($this);
    }

    /**
     * Pause read and write on the resource
     * @return void
     */
    public function pauseWrite()
    {
        $this->_getResourceManager()->pauseWrite($this);
    }

    /**
     * Resume read and write on the resource
     */
    public function resume()
    {
        $this->_getResourceManager()->resume($this);
    }

    /**
     * Resume read and write on the resource
     */
    public function resumeRead()
    {
        $this->_getResourceManager()->resumeRead($this);
    }

    /**
     * Resume read and write on the resource
     */
    public function resumeWrite()
    {
        $this->_getResourceManager()->resumeWrite($this);
    }

    /**
     * @return bool|string
     */
    public function read()
    {
        return $this->_getResourceManager()->read($this);
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function write($message)
    {
        return $this->_getResourceManager()->write($this, $message);
    }

    /**
     * @param bool $graceful
     * @return bool
     */
    public function close($graceful = true)
    {
        return $this->_getResourceManager()->close($this, $graceful);
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
    public function isReadable()
    {
        return $this->handle->isReadable();
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->handle->isWritable();
    }

    /**
     * @return bool
     */
    public function isConnecting()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_CONNECTING;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_CONNECTED;
    }

    /**
     * @return bool
     */
    public function isPaused()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_PAUSED;
    }

    /**
     * @return bool
     */
    public function isPausedRead()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_PAUSED_READ;
    }

    /**
     * @return bool
     */
    public function isPausedWrite()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_PAUSED_WRITE;
    }

    /**
     * @return bool
     */
    public function isClosing()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_CLOSING;
    }

    /**
     * @return bool
     */
    public function isDisconnected()
    {
        return $this->getStatus() === ResourceStatusStore::STATUS_DISCONNECTED;
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
        return $this->handle->eof();
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
        if (!isset($this->storage[$name])) {
            return null;
        }

        return $this->storage[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->storage[$name] = $value;
    }

    /**
     * @param ResourceManager $resourceManager
     * @param array $context
     */
    protected function _setResourceManager(ResourceManager $resourceManager, array $context = null)
    {
        if (!is_null($this->resourceManager)) {
            $this->resourceManager->detach($this);
        }
        $this->resourceManager = $resourceManager;
        $resourceManager->attach($this, $context);
    }

    protected function _getResourceManager($required = true)
    {
        if ($required && is_null($this->resourceManager)) {
            throw new \Exception();
        }

        return $this->resourceManager;
    }

}