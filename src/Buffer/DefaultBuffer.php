<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 14:40
 */

namespace trochilidae\Sockets\Buffer;

use trochilidae\Sockets\Buffer;
use trochilidae\Sockets\Handle;

class DefaultBuffer implements Buffer
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
     * @param Handle $handle
     * @param string $message
     *
     * @return bool
     */
    public function write(Handle $handle, $message)
    {
        $key = $handle->toInt();
        if (!isset($this->queue[$key])) {
            $this->queue[$key] = [];
        }
        $this->queue[$key][] = $message;
        $this->length += strlen($message);

        return $this->getSize() >= $this->maxSize;
    }

    /**
     * @param Handle $handle
     *
     * @return bool
     */
    public function isEmpty(Handle $handle)
    {
        $key = $handle->toInt();
        return empty($this->queue[$key]);
    }

    /**
     * @param Handle $handle
     * @param        $message
     *
     * @return bool
     */
    public function unshift(Handle $handle, $message)
    {
        $key = $handle->toInt();
        if (!isset($this->queue[$key])) {
            $this->queue[$key] = [];
        }
        array_unshift($this->queue[$key], $message);
        $this->length += strlen($message);

        return $this->getSize() >= $this->maxSize;
    }

    /**
     * @param Handle $handle
     *
     * @return mixed|null
     */
    public function shift(Handle $handle)
    {
        $key = $handle->toInt();

        if (!isset($this->queue[$key])) {
            return null;
        }

        $message = array_shift($this->queue[$key]);
        if (!is_null($message)) {
            $this->length -= strlen($message);

            return $message;
        }

        return null;
    }

    /**
     * @param Handle $handle
     *
     * @return array
     */
    public function pull(Handle $handle)
    {
        $key      = $handle->toInt();
        $messages = [];
        if (isset($this->queue[$key])) {
            $messages = $this->queue[$key];
            unset($this->queue[$key]);
            $this->length -= $this->calculateSize($messages);
        }

        return $messages;
    }

    /**
     * @param Handle $handle
     * @param array  $messages
     */
    public function set(Handle $handle, array $messages = [])
    {
        $key = $handle->toInt();
        if (isset($this->queue[$key])) {
            $this->length -= $this->calculateSize($this->queue[$key]);
        }
        $this->queue[$key] = $messages;
        $this->length += $this->calculateSize($messages);
    }

    public function getSize(){
        return $this->length;
    }

    /**
     * @param array $messages
     *
     * @return int
     */
    protected function calculateSize(array &$messages)
    {
        $len = 0;
        array_walk($messages, function ($value) use (&$len) {
            $len += strlen($value);
        });

        return $len;
    }

} 