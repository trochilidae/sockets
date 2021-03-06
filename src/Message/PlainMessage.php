<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/19
 * Time: 23:18
 */

namespace trochilidae\Sockets\Message;

use trochilidae\Sockets\Message;
use trochilidae\Sockets\Exceptions\InvalidArgumentException;

class PlainMessage implements Message {

    /**
     * @var string
     */
    protected $string = "";

    /**
     * @param string $string
     *
     * @throws \trochilidae\Sockets\Exceptions\InvalidArgumentException
     */
    function __construct($string = "")
    {
        if(!is_string($string)){
            throw new InvalidArgumentException();
        }

        $this->string = $string;
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->string;
    }
}