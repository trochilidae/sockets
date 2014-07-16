<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 21:10
 */

namespace trochilidae\Sockets\Handles;

use trochilidae\Sockets\Handle;

class DefaultHandle extends Handle
{
    /**
     * @var resource $handle
     */
    protected $handle;
    /**
     * @var array
     */
    protected $meta;
    /**
     * @var bool
     */
    protected $isReadable;
    /**
     * @var bool
     */
    protected $isWritable;

    /**
     * @param resource $handle
     * @param null     $flags
     */
    public function __construct($handle, $flags = null)
    {
        parent::__construct($handle, $flags);
        $this->meta       = stream_get_meta_data($this->handle);
        $this->access     = stream_get_access($this->handle, $this->meta);
        $this->isReadable = $this->access["read"];
        $this->isWritable = $this->access["write"];
    }

    /**
     * @param array $context
     * @return bool
     */
    public function setContext(array $context = [])
    {
        return stream_context_set_params($this->handle, $context);
    }

    /**
     * @param $length
     *
     * @return bool|string
     */
    public function read($length)
    {
        if (!$this->isReadable || $this->isClosed()) return false;

        return fread($this->handle, $length);
    }

    /**
     * @param $message
     *
     * @return bool|int
     */
    public function write($message)
    {
        if (!$this->isWritable || $this->isClosed()) return false;

        return fwrite($this->handle, $message);
    }

    /**
     * @param $length
     * @return string
     */
    public function peek($length){
        return stream_socket_recvfrom($this->handle, $length, STREAM_PEEK);
    }

    /**
     * @return bool
     */
    public function close(){
        return fclose($this->handle);
    }

    /**
     * @return bool
     */
    public function isSync()
    {
        return $this->meta["blocked"] === true;
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->isReadable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->isWritable;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return stream_closed($this->handle);
    }

    /**
     * @return bool
     */
    public function eof()
    {
        return stream_eof($this->handle);
    }

    /**
     * @return bool
     */
    public function setBlocking()
    {
        if ($this->isClosed()) return false;

        $success = stream_set_blocking($this->handle, 1);
        if ($success) {
            $this->meta["blocked"] = true;
        }

        return $success;
    }


    /**
     * @return bool
     */
    public function setNonBlocking()
    {
        if ($this->isClosed()) return false;

        $success = stream_set_blocking($this->handle, 0);
        if ($success) {
            $this->meta["blocked"] = false;
        }

        return $success;
    }

}