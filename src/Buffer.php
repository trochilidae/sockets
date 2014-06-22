<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 14:40
 */

namespace trochilidae\Sockets;

class Buffer
{
    //TODO: Impose strict limits if needed such as pausing all resources using the buffer

    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var int
     */
    protected $length = 0;

    /**
     * @var int
     */
    protected $maxSize = 1024;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param string                        $message
     *
     * @return bool
     */
    public function write(Resource $resource, $message)
    {
        $key = $this->getKey($resource->getHandle());
        if (!isset($this->queue[$key])) {
            $this->queue[$key] = [];
        }
        $this->queue[$key][] = $message;
        $this->length += strlen($message);

        return $this->length >= $this->maxSize;
    }

    public function isEmpty(Resource $resource){
        $key = $this->getKey($resource->getHandle());
        return empty($this->queue[$key]);
    }

    public function unshift(Resource $resource, $message){
        $key = $this->getKey($resource->getHandle());
        if (!isset($this->queue[$key])) {
            $this->queue[$key] = [];
        }
        array_unshift($this->queue[$key], $message);
        $this->length += strlen($message);

        return $this->length >= $this->maxSize;
    }

    /**
     * @param resource $handle
     *
     * @return mixed|null
     */
    public function pop($handle)
    {
        $key = $this->getKey($handle);

        if (!isset($this->queue[$key])) {
            return null;
        }

        $message = array_pop($this->queue[$key]);
        if (!is_null($message)) {
            $this->length -= strlen($message);
            return $message;
        }

        return null;
    }

    /**
     * @param resource $handle
     *
     * @return array
     */
    public function pull($handle)
    {
        $key      = $this->getKey($handle);
        $messages = [];
        if (isset($this->queue[$key])) {
            $messages = $this->queue[$key];
            unset($this->queue[$key]);
            $this->length -= $this->getSize($messages);
        }

        return $messages;
    }

    /**
     * @param resource $handle
     * @param array    $messages
     */
    public function set($handle, array $messages = [])
    {
        $key = (int)$handle;
        if (isset($this->queue[$key])) {
            $this->length -= $this->getSize($this->queue[$key]);
        }
        $this->queue[$key] = $messages;
        $this->length += $this->getSize($messages);
    }

    /**
     * @param array $messages
     *
     * @return int
     */
    protected function getSize(array &$messages)
    {
        $len = 0;
        array_walk($messages, function ($value) use (&$len) {
            $len += strlen($value);
        });

        return $len;
    }

    /**
     * @param resource $handle
     *
     * @return int
     */
    protected function getKey($handle)
    {
        return (int)$handle;
    }

} 