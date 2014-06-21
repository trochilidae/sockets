<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 19:13
 */

namespace trochilidae\Sockets;

use Illuminate\Events\Dispatcher;
use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use React\EventLoop\Timer\Timer;
use React\EventLoop\LoopInterface;

class ResourceManager
{

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var array
     */
    protected $protocol = [];

    /**
     * @var array
     */
    protected $asyncHandle;

//    /**
//     * @var \SplObjectStorage
//     */
//    protected $resources;

    /**
     * @var array
     */
    protected $handles;

    /**
     * @var array
     */
    protected $currentResourceProtocol = [];

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * @param \Illuminate\Events\Dispatcher $events
     * @param LoopInterface                 $loop
     */
    function __construct(Dispatcher $events, LoopInterface $loop)
    {
        $this->loop        = $loop;
//        $this->resources   = new \SplObjectStorage;
        $this->asyncHandle = [$this, "handleAsyncRead"];
        $this->events = $events;
    }

    /**
     * @param $protocol
     *
     * @throws Exceptions\InvalidArgumentException
     */
    public function setProtocol($protocol)
    {
        if (!is_array($protocol) || (!is_object($protocol) && $protocol instanceof Protocol)) {
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

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function attach(Resource $resource)
    {
//        $this->resources->attach($resource);
        $this->handles[(int)$resource->getHandle()] = & $resource;
        if ($resource->isPaused()) {
            $resource->resume();
        }

    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function detach(Resource $resource)
    {
        $resource->pause();
//        $this->resources->detach($resource);
        unset($this->handles[(int)$resource->getHandle()]);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function pause(Resource $resource)
    {
        if (!$resource->isSync() && !$resource->isPaused()) {
            $this->removeReadStream($resource->getHandle());
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function resume(Resource $resource)
    {
        if ($resource->isReadable() && !$resource->isSync() && $resource->isPaused()) {
            $this->addReadStream($resource->getHandle());
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool|null|Message\MessageBuilder
     */
    public function read(Resource $resource)
    {
        if (!$resource->isReadable() || $resource->isClosed()) {
            return null;
        }else if($resource->isEnd()){
//           $this->events->fire("resource.end", [$resource], true);
//            return false;
            return null;
        }

        $messageBuilder = $resource->getMessageBuilder();
        $protocol       = $this->getCurrentProtocol($resource->getHandle());

        if (is_null($protocol)) { //no protocol set
            $message = $messageBuilder->read(1024);
            $messageBuilder->setMessage($message);
        } else if ($protocol instanceof Protocol) {
            $ret = $protocol->onRead($resource, $messageBuilder);
            if ($ret === false) { //protocol not finished
                if ($resource->isSync()) {
                    while (($ret = $protocol->onRead($resource, $messageBuilder)) !== false) { //retry until protocol finished
                        usleep(1000); //decrease cpu ticks
                    };
                } else {
                    return false;
                }
            }
            $this->incrementProtocolOffset($resource->getHandle());
            if ($this->getCurrentProtocol($resource->getHandle()) !== false) { //if not done with protocols
                if ($resource->isSync()) {
                    return $this->read($resource); //recurse down
                }

                return true;
            }
        }

        $this->events->fire("resource.message", [$resource, $messageBuilder->getMessage()]);

        return $messageBuilder->getMessage();
    }

    /**
     * @param \trochilidae\Sockets\Resource          $resource
     * @param                                        $message
     */
    public function write(Resource $resource, $message)
    {
        //TODO: Run through protocols and write the final message to the transport
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool
     */
    public function close(Resource $resource)
    {
        $this->loop->nextTick(function(LoopInterface $loop) use($resource){
            $loop->removeStream($resource->getHandle());
            $resource->getTransport()->close();
        });
    }

    /**
     * @param resource $handle
     */
    public function handleAsyncRead($handle)
    {
        $this->removeReadStream($handle);

        /**
         * @var \trochilidae\Sockets\Resource $resource
         */
        $resource = $this->handles[(int)$handle];

        $ret = $this->read($resource);

        if (!is_null($ret)) {
            if ($this->hasMoreData($resource)) {
                $callback = & $this->asyncHandle;
                $this->loop->addTimer(Timer::MIN_INTERVAL, function () use ($callback, $handle) {
                    $callback[0]->$callback[1]($handle);
                });
            } else {
                $this->addReadStream($handle);
            }
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool
     */
    protected function hasMoreData(Resource $resource)
    {
        return strlen($resource->getMessageBuilder()->peak(1)) > 0;
    }

    /**
     * @param resource $handle
     *
     * @return mixed
     */
    protected function incrementProtocolOffset($handle)
    {
        $key = (int)$handle;
        if (!isset($this->currentResourceProtocol[$key])) {
            $this->currentResourceProtocol[$key] = 0;
        } else {
            $this->currentResourceProtocol[$key]++;
        }

        return $this->currentResourceProtocol[$key];
    }

    /**
     * @param resource $handle
     *
     * @return mixed
     */
    protected function getCurrentProtocolOffset($handle)
    {
        $key = (int)$handle;
        if (!isset($this->currentResourceProtocol[$key])) {
            $this->currentResourceProtocol[$key] = 0;
        }

        return $this->currentResourceProtocol[$key];
    }

    /**
     * @param resource $handle
     *
     * @return bool|null
     */
    protected function getCurrentProtocol($handle)
    {
        if (empty($this->protocol)) {
            return null;
        }

        $offset = $this->getCurrentProtocolOffset($handle);

        if (isset($this->protocol[$offset])) {
            return $this->protocol[$offset];
        }

        return false;
    }

    /**
     * @param resource $handle
     */
    protected function addReadStream($handle)
    {
        $this->loop->addReadStream($handle, $this->asyncHandle);
    }

    /**
     * @param resource $handle
     */
    protected function removeReadStream($handle)
    {
        $this->loop->removeReadStream($handle);
    }


} 