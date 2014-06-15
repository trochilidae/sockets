<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 19:13
 */

namespace krinfreschi\Stream;

use krinfreschi\Stream\Exceptions\InvalidArgumentException;
use React\EventLoop\LoopInterface;

class ResourceManager {

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    /**
     * @var \SplDoublyLinkedList
     */
    protected $protocol;
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
        $this->protocol = new \SplDoublyLinkedList();
        foreach ($protocol as $item) {
            if (gettype($item) !== "object") {
                throw new InvalidArgumentException("Expected object got [" . gettype($item) . "]");
            } else if (!($item instanceof Protocol)) {
                throw new InvalidArgumentException("Expected Protocol got [" . get_class($item) . "]");
            }
            $this->protocol->push($item);
        }
    }

    public function attach(Resource $resource)
    {
        $this->resources->attach($resource);
    }

    public function detach(Resource $resource)
    {
        $this->resources->detach($resource);
    }

    public function pause(Resource $resource)
    {

    }

    public function resume(Resource $resource)
    {

    }

    public function write(Resource $resource, $message)
    {

    }

    public function handleRead(){

    }

    public function read($resource)
    {
    }

    public function next($resource)
    {
    }
} 