<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 19:13
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use React\EventLoop\LoopInterface;

class ResourceManager {

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    protected $protocol = [];

    /**
     * @var \SplObjectStorage
     */
    protected $resources;



    function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->resources = new \SplObjectStorage;
    }

    /**
     * @param $protocol
     *
     * @throws Exceptions\InvalidArgumentException
     */
    public function setProtocol($protocol)
    {
        if(!is_array($protocol) || (!is_object($protocol) && $protocol instanceof Protocol)){
            throw new InvalidArgumentException();
        }
        $this->protocol = [];
        foreach ($protocol as $item) {
            if (gettype($item) !== "object") {
                throw new InvalidArgumentException("Expected object got [" . gettype($item) . "]");
            } else if (!($item instanceof Protocol)) {
                throw new InvalidArgumentException("Expected Protocol got [" . get_class($item) . "]");
            }
            $this->protocol[] = $item;
        }
    }

    public function attach(Resource $resource)
    {
        $this->resources->attach($resource);
        if($resource->isPaused()){
            $this->resume($resource);
        }

    }

    public function detach(Resource $resource)
    {
        $resource->pause();
        $this->resources->detach($resource);
    }

    public function pause(Resource $resource)
    {
        if(!$resource->isPaused()){
            $this->loop->removeReadStream($resource->getHandle());
        }
    }

    public function resume(Resource $resource)
    {
        if($resource->isPaused()){
            $this->loop->addReadStream($resource->getHandle(), array($this, "handleRead"));
        }
    }

    public function write(Resource $resource, $message)
    {

    }

    public function handleRead(){

    }

} 