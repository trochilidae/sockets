<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 13:55
 */

namespace krinfreschi\Stream;

use krinfreschi\Stream\Exceptions\InvalidArgumentException;
use krinfreschi\Stream\Support\ObjectTrait;
use krinfreschi\Stream\Transports\TransportInterface;
use React\EventLoop\LoopInterface;
use krinfreschi\Stream\Exceptions\InvalidResourceException;

class Resource
{
    use ObjectTrait;

    const Async = 0x1;
    const Sync  = 0x2;

    /**
     * @var TransportInterface
     */
    protected $transport;

    protected $meta;

    protected $access;

    /**
     * @var bool
     */
    protected $isReadable;

    /**
     * @var bool
     */
    protected $isWritable;

    protected $isPaused;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * @param TransportInterface                              $transport
     * @param                                                 $flags
     *
     * @param Buffer                                          $buffer
     * @param ResourceManager                                 $resourceManager
     *
     * @throws Exceptions\InvalidResourceException
     */
    public function __construct(TransportInterface $transport, $flags = null, Buffer $buffer, ResourceManager $resourceManager)
    {
        if ($transport->isClosed()) {
            throw new InvalidResourceException();
        }

        $this->transport = $transport;
        $this->resourceManager = $resourceManager;
        $this->buffer = $buffer;

        if (($flags & self::Sync) != 0) {
            $transport->setBlocking();
        }else{
            $transport->setNonBlocking();
        }

        $this->resourceManager->attach($this);
    }

    /**
     * @param ResourceManager $resourceManager
     */
    public function setResourceManager(ResourceManager $resourceManager){
        $this->resourceManager = $resourceManager;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    public function isPaused(){
        return $this->isPaused;
    }

    function pause(){
        $this->resourceManager->pause($this);
        $this->isPaused = true;
    }

    function resume(){
        $this->resourceManager->resume($this);
        $this->isPaused = false;
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function write($message)
    {
        if(!$this->isPaused()){
            return $this->buffer->write($message);
        }
        return false;
    }

    public function getHandle(){
        return $this->transport->getHandle();
    }

}