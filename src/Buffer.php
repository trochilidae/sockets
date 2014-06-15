<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 14:40
 */

namespace trochilidae\Sockets;


use trochilidae\Sockets\Exceptions\InvalidArgumentException;

class Buffer {

    /**
     * @var array
     */
    protected $queue = [];

    protected $maxSize = 1024;

    /**
     * @var \trochilidae\Sockets\Resource
     */
    protected $resource;


    public function __construct()
    {

    }

    /**
     * @param \trochilidae\Sockets\Resource|Resource $resource
     */
    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }


    /**
     * @param string $message
     *
     * @throws Exceptions\InvalidArgumentException
     * @return bool
     */
    public function write($message){
        $this->queue[] = $message;
        return false;
    }

    protected function getSize(){
        $size = 0;
        array_walk($this->queue, function($value) use (&$size){
            $size += strlen($value);
        });
        return $size;
    }

} 