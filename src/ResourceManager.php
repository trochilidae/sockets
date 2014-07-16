<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 19:13
 */

namespace trochilidae\Sockets;

use Evenement\EventEmitter;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use trochilidae\Sockets\Message\PlainMessage;
use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;
use trochilidae\Sockets\Protocols\ProtocolGroup;

class ResourceManager
{

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * @var array
     */
    protected $asyncReadHandle = "handleAsyncRead";

    /**
     * @var array
     */
    protected $asyncWriteHandle = "handleAsyncWrite";

    /**
     * @var array
     */
    protected $resources = [];

    protected $resourceStatus = [];

    /**
     * @var array
     */
    protected $handles;

    /**
     * @var array
     */
    protected $pendingMessages = [];

    /**
     * @var EventEmitter
     */
    protected $emitter;

    protected $context = [];

    /**
     * @param LoopInterface $loop
     */
    function __construct(LoopInterface $loop = null)
    {
        if(is_null($loop)){
            $loop = Factory::create();
        }
        $this->loop      = $loop;
        $this->emitter = new EventEmitter();
    }

    /**
     * @param EventEmitter $emitter
     */
    public function setEventEmitter(EventEmitter $emitter){
        $this->emitter = $emitter;
    }

    /**
     * @return \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|LoopInterface|\React\EventLoop\StreamSelectLoop
     */
    public function getLoop(){
        return $this->loop;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return int
     */
    public function getStatus(Resource $resource){
        $handle = $resource->getHandle();
        if(isset($this->resourceStatus[$handle->toInt()])){
            return $this->resourceStatus[$handle->toInt()]->getStatus();
        }
        return ResourceStatusStore::STATUS_DISCONNECTED;
    }

    /**
     * @return string
     */
    public function protocol(){
        return $this->protocol->getName();
    }

    /**
     * @param Protocol $protocol
     *
     * @throws Exceptions\InvalidArgumentException
     */
    public function setProtocol(Protocol $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param array $context
     */
    public function attach(Resource $resource, array $context = null)
    {
        $handle = $resource->getHandle();

        $this->resources[$handle->toInt()] = $resource;
        $this->handles[$handle->toInt()] = $handle;
        $this->resourceStatus[$handle->toInt()] = new ResourceStatusStore();

        if(!is_null($context)){
            $context = array_merge($context, $this->context);
            $handle->setContext($context);
        }

        $this->resumeRead($resource);
        $this->resumeWrite($resource);

        $this->setStatus($handle, ResourceStatusStore::STATUS_CONNECTING);
        $this->emitter->emit("connecting", [$resource]);

        $this->open($resource);

        if(!$this->protocol->requiresHandshake()){
            $this->setStatus($handle, ResourceStatusStore::STATUS_CONNECTED);
            $this->emitter->emit("connected", [$resource]);
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function detach(Resource $resource)
    {
        $this->pause($resource);
        $handle = $resource->getHandle();

        $this->setStatus($handle, ResourceStatusStore::STATUS_DISCONNECTED);
        $this->emitter->emit("disconnect", [$resource]);

        unset($this->resources[$handle->toInt()]);
        unset($this->handles[$handle->toInt()]);
        unset($this->resourceStatus[$handle->toInt()]);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function pause(Resource $resource)
    {
        if ($resource->isSync()) {
            return;
        }

        $handle = $resource->getHandle();

        $this->setStatus($handle, ResourceStatusStore::STATUS_PAUSED);

        $this->disableRead($handle);
        $this->disableWrite($handle);

    }

    public function pauseRead(Resource $resource){
        if ($resource->isSync() || $resource->isPaused() || $resource->isPausedRead()) {
            return;
        }else if($resource->isPausedWrite()){
            $this->pause($resource);
            return;
        }
        $handle = $resource->getHandle();
        $this->setStatus($handle, ResourceStatusStore::STATUS_PAUSED_READ);
        $this->disableRead($handle);
    }

    public function pauseWrite(Resource $resource){
        if ($resource->isSync() || $resource->isPaused() || $resource->isPausedWrite()) {
            return;
        }else if($resource->isPausedRead()){
            $this->pause($resource);
            return;
        }
        $handle = $resource->getHandle();
        $this->setStatus($handle, ResourceStatusStore::STATUS_PAUSED_WRITE);
        $this->disableWrite($handle);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function resume(Resource $resource)
    {
        if ($resource->isSync()) {
            return;
        }

        $handle = $resource->getHandle();

        $this->restoreStatus($handle);

        if ($resource->isReadable()) {
            $this->enableRead($handle);
        }

        if ($resource->isWritable()) {
            $this->enableWrite($handle);
        }

    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function resumeRead(Resource $resource){
        if ($resource->isSync() || (!$resource->isDisconnected() && (!$resource->isPaused() || !$resource->isPausedRead()))) {
            return;
        }
        $handle = $resource->getHandle();

        $this->restoreStatus($handle);
        if ($resource->isReadable()) {
            $this->enableRead($handle);
        }
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     */
    public function resumeWrite(Resource $resource){
        if ($resource->isSync() || (!$resource->isDisconnected() && (!$resource->isPaused() || !$resource->isPausedWrite()))) {
            return;
        }
        $handle = $resource->getHandle();

        $this->restoreStatus($handle);
        if ($resource->isWritable()) {
            $this->enableWrite($handle);
        }
    }


    /**
     * @param resource $handle
     */
    public function handleAsyncRead($handle)
    {
        /**
         * @var \trochilidae\Sockets\Resource $resource
         */
        $resource = $this->resources[(int)$handle];

        $ret = $this->_read($resource);

        if (!is_null($ret)) {
            if (!$this->hasMoreData($resource)) {
                unset($this->pendingMessages[$resource->id]);
                return;
            }
            $callback = [$this, $this->asyncReadHandle];
            $this->loop->nextTick(function () use ($callback, $handle) {
                $callback[0]->$callback[1]($handle);
            });
        }
    }

    /**
     * @param resource $handle
     */
    public function handleAsyncWrite($handle)
    {
        /**
         * @var \trochilidae\Sockets\Handle $rHandle
         */
        $rHandle = $this->handles[(int)$handle];

        $buffer   = $rHandle->getBuffer();
        if(!$buffer->isEmpty()){
            $message = $buffer->shift();
            $written = $rHandle->write($message);
            if($written != strlen($message)){
                $buffer->unshift(substr($message, $written));
            }
        }
    }

    protected function open(Resource $resource)
    {
        $this->protocol->onOpen($resource);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool|null|Message
     */
    public function read(Resource $resource)
    {
        if($resource->isSync()){
            return false;
        }
        return $this->_read($resource);
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return bool|null|Message|PlainMessage
     */
    protected function _read(Resource $resource)
    {
        if (!$resource->isReadable() || $resource->isClosed()) {
            return null;
        } else if ($resource->isEnd()) {
            $resource->close();
            return null;
        }

        /**
         * @var ReadableMessageEnvelope $messageEnvelope
         */
        if (isset($this->pendingMessages[$resource->id])) {
            $messageEnvelope = $this->pendingMessages[$resource->id];
        } else {
            $messageEnvelope = ReadableMessageEnvelope::make($resource, $this->protocol);
            $this->pendingMessages[$resource->id] = $messageEnvelope;
        }


        if (!$this->protocol) { //no protocol set
            $message = new PlainMessage($messageEnvelope->read(1024));
        } else {
            $firstIT = true;
            do {
                if(!$firstIT){
                    usleep(1000);
                }
                $ret = $this->protocol->onRead($messageEnvelope, $resource);
                if (is_string($ret) || $ret instanceof Message) {
                    $messageEnvelope->setMessage($ret);
                }
                $firstIT = false;
            }while($resource->isSync() && $ret === false);

            if ($ret === false) { //if not done with protocols
                return false;
            }
            $message = $messageEnvelope->getMessage();
        }

        unset($this->pendingMessages[$resource->id]);
        $handle = $resource->getHandle();

        //TODO check for last stateful rather than assuming connected when done with first message
        if($resource->isConnecting()){
            $this->setStatus($handle, ResourceStatusStore::STATUS_CONNECTED);
            $this->emitter->emit("connected", [$resource]);
        }

        if ((string)$message) {
            $this->emitter->emit("data", [$message, $resource]);
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
        if($message instanceof WritableMessageEnvelope){
            $messageEnvelope = $message;
        }else {
            $messageEnvelope = WritableMessageEnvelope::make($resource, $this->protocol, $message);
        }

        $firstIT = true;
        do {
            if(!$firstIT){
                usleep(1000);
            }
            $ret = $this->protocol->onWrite($messageEnvelope, $resource);
            $firstIT = false;
        }while($resource->isSync() && $ret === false);

        $message = (string)$messageEnvelope->getMessage();

        $handle = $resource->getHandle();

        if($resource->isSync()){
            return $handle->write($message);
        }else if($ret !== false){
            $handle->getBuffer()->write($message);
        }
        return true;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @param bool $graceful
     * @return bool
     */
    public function close(Resource $resource, $graceful = true)
    {
        if($resource->isClosing()){
            return null;
        }

        $handle = $resource->getHandle();
        $this->setStatus($handle, ResourceStatusStore::STATUS_CLOSING);

        $this->emitter->emit("close", [$resource]);

        $this->protocol->onClose($resource);

        if($resource->isSync()){
            $this->detach($resource);
            $close = $handle->close();
            $this->setStatus($handle, ResourceStatusStore::STATUS_CLOSED);
            $this->emitter->emit("end", [$resource]);
            return $close;
        }

        $buffer = $handle->getBuffer();

        $close = null;
        $close = function (LoopInterface $loop) use ($handle, $resource, $buffer, $graceful, &$close) {
            if($graceful !== true || $buffer->isEmpty($handle)){
                $loop->removeStream($handle->get());
                $this->detach($resource);
                $handle->close();
                $this->setStatus($handle, ResourceStatusStore::STATUS_CLOSED);
                $this->emitter->emit("end", [$resource]);
                return;
            }
            $loop->futureTick($close);
        };

        $this->loop->futureTick($close);
        return true;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return bool
     */
    protected function hasMoreData(Resource $resource)
    {
        /**
         * @var ReadableMessageEnvelope $messageEnvelope
         */
        if(!isset($this->pendingMessages[$resource->id])){
            $this->pendingMessages[$resource->id] = ReadableMessageEnvelope::make($resource, $this->protocol);
        }
        $messageEnvelope = $this->pendingMessages[$resource->id];
        return strlen($messageEnvelope->readAndStore(1)) > 0;
    }

    protected function setStatus(Handle $handle, $status){
        if(isset($this->resourceStatus[$handle->toInt()])){
            $this->resourceStatus[$handle->toInt()]->setStatus($status);
        }
    }

    protected function restoreStatus(Handle $handle){
        if(isset($this->resourceStatus[$handle->toInt()])){
            $this->resourceStatus[$handle->toInt()]->rememberPreviousStatus();
        }
    }

    /**
     * @param Handle $handle
     */
    protected function enableRead(Handle $handle)
    {
        $this->loop->addReadStream($handle->get(), [$this, $this->asyncReadHandle]);
    }

    /**
     * @param Handle $handle
     */
    protected function disableRead(Handle $handle)
    {
        $this->loop->removeReadStream($handle->get());
    }

    /**
     * @param Handle $handle
     */
    protected function enableWrite(Handle $handle)
    {
        $this->loop->addWriteStream($handle->get(), [$this, $this->asyncWriteHandle]);
    }

    /**
     * @param Handle $handle
     */
    protected function disableWrite(Handle $handle)
    {
        $this->loop->removeWriteStream($handle->get());
    }
}