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
use trochilidae\Sockets\Message\PlainMessage;

class ResourceManager
{

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \SplDoublyLinkedList
     */
    protected $protocols;

    /**
     * @var array
     */
    protected $asyncReadHandle = "handleAsyncRead";

    /**
     * @var array
     */
    protected $asyncWriteHandle = "handleAsyncwrite";

    /**
     * @var \SplObjectStorage
     */
    protected $resources;

    /**
     * @var array
     */
    protected $handles;

    protected $pendingMessages = [];

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
        $this->loop      = $loop;
        $this->resources = new \SplObjectStorage;
        $this->events    = $events;
        $this->protocols = new \SplDoublyLinkedList();
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
        $this->protocols = new \SplDoublyLinkedList();
        foreach ($protocol as $item) {
            if (gettype($item) !== "object") {
                throw new InvalidArgumentException("Expected object got [" . gettype($item) . "]");
            } else if (!($item instanceof Protocol)) {
                throw new InvalidArgumentException("Expected Protocol got [" . get_class($item) . "]");
            }
            $this->protocols->push($item);
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function attach(Resource $resource)
    {
        $this->resources->attach($resource);
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
        $this->resources->detach($resource);
        unset($this->handles[(int)$resource->getHandle()]);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function pause(Resource $resource)
    {
        if (!$resource->isPaused() || $resource->isSync()) {
            return;
        }

        $handle = $resource->getHandle();

        $this->disableRead($handle);
        $this->disableWrite($handle);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function resume(Resource $resource)
    {
        if (!$resource->isPaused() || $resource->isSync()) {
            return;
        }

        $handle = $resource->getHandle();

        if ($resource->isReadable()) {
            $this->enableRead($handle);
        }

        if ($resource->isWritable()) {
            $this->enableWrite($handle);
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool|null|Message
     */
    public function read(Resource $resource)
    {
        if (!$resource->isReadable() || $resource->isClosed()) {
            return null;
        }

        /**
         * @var MessageEnvelope $messageEnvelope
         */
        if(isset($this->pendingMessages[$resource->id])){
            $messageEnvelope = $this->pendingMessages[$resource->id];
            $protocols = $messageEnvelope->getProtocols();
        }else {
            $protocols = clone $this->protocols;
            $protocols->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO);
            $protocols->rewind();
            $messageEnvelope = $this->pendingMessages[$resource->id] = new MessageEnvelope($resource, $protocols);
        }

        if ($protocols->isEmpty()) { //no protocol set
            $message = new PlainMessage($resource->getStreamReader()->read(1024));
        } else {
            $protocol = $protocols->current();
            $streamReader = $resource->getStreamReader();
            $ret = $protocol->onRead($streamReader, $messageEnvelope);
            if ($ret === false) { //protocol not finished
                if ($resource->isSync()) {
                    while (($ret = $protocol->onRead($streamReader, $messageEnvelope)) !== false) { //retry until protocol finished
                        usleep(1000); //decrease cpu ticks
                    };
                } else {
                    return false;
                }
            }else {
                if(is_string($ret)){
                    $ret = new PlainMessage($ret);
                }
                if($ret instanceof Message){
                    $messageEnvelope->setMessage($ret);
                }
            }

            $protocols->next();
            if ($protocols->valid()) { //if not done with protocols
                if ($resource->isSync()) {
                    return $this->read($resource); //recurse down
                }
                return true;
            }

            $message = $messageEnvelope->getMessage();
            unset($this->pendingMessages[$resource->id]);
        }

        if((string)$message){
            $this->events->fire("resource.message", [$resource, $message]);
        }

        if ($resource->isEnd()) {
            $this->events->fire("resource.end", [$resource]);
        }

        return $message;
    }

    /**
     * @param \trochilidae\Sockets\Resource          $resource
     * @param                                        $message
     *
     * @return bool|int|null
     */
    public function write(Resource $resource, $message)
    {
        if($message instanceof MessageEnvelope){
            $protocols = $message->getProtocols();
            $messageEnvelope = $message;
        }else {
            $protocols = clone $this->protocols;
            $protocols->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO);
            $protocols->rewind();
            $messageEnvelope = new MessageEnvelope($resource, $protocols);
            $messageEnvelope->setMessage(new PlainMessage($message));
        }

        $ret = null;
        while($protocols->valid()){
            $protocol = $protocols->current();
            $ret = $protocol->onWrite($messageEnvelope);
            if ($ret === false) {
                if ($resource->isSync()) {
                    while (($ret = $protocol->onWrite($messageEnvelope)) !== false) {
                        usleep(1000); //decrease cpu ticks
                    }
                }else{
                    break;
                }
            }else {
                if(is_string($ret)){
                    $ret = new PlainMessage($ret);
                }
                if($ret instanceof Message){
                    $messageEnvelope->setMessage($ret);
                }
//                var_dump((string)$ret);
            }
            $protocols->next();
        }

        $message = (string)$messageEnvelope->getMessage();
        if($resource->isSync()){
            return $resource->getTransport()->write($message);
        }
        if($ret !== false){
            $resource->getBuffer()->write($resource, $message);
        }
        return true;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool
     */
    public function close(Resource $resource)
    {
        $this->loop->nextTick(function (LoopInterface $loop) use ($resource) {
            $loop->removeStream($resource->getHandle());
            $resource->getTransport()->close();
        });
    }

    /**
     * @param resource $handle
     */
    public function handleAsyncRead($handle)
    {
        /**
         * @var \trochilidae\Sockets\Resource $resource
         */
        $resource = $this->handles[(int)$handle];

        $ret = $this->read($resource);

        if (!is_null($ret)) {
            if ($this->hasMoreData($resource)) {
                $callback = [$this, $this->asyncReadHandle];
                $this->loop->nextTick(function () use ($callback, $handle) {
                    $callback[0]->$callback[1]($handle);
                });
            }
        }
    }

    /**
     * @param resource $handle
     */
    public function handleAsyncWrite($handle)
    {
        /**
         * @var \trochilidae\Sockets\Resource $resource
         */
        $resource = $this->handles[(int)$handle];
        $buffer   = $resource->getBuffer();
        if(!$buffer->isEmpty($resource)){
            $message = $buffer->pop($handle);
            $written = $resource->getTransport()->write($message);
            if($written != strlen($message)){
                $buffer->unshift($resource, substr($message, $written));
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
        return strlen($resource->getStreamReader()->peak(1)) > 0;
    }

    /**
     * @param resource $handle
     */
    protected function enableRead($handle)
    {
        $this->loop->addReadStream($handle, [$this, $this->asyncReadHandle]);
    }

    /**
     * @param resource $handle
     */
    protected function disableRead($handle)
    {
        $this->loop->removeReadStream($handle);
    }

    /**
     * @param resource $handle
     */
    protected function enableWrite($handle)
    {
        $this->loop->addWriteStream($handle, [$this, $this->asyncWriteHandle]);
    }

    /**
     * @param resource $handle
     */
    protected function disableWrite($handle)
    {
        $this->loop->removeWriteStream($handle);
    }

} 