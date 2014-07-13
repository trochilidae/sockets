<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/19
 * Time: 16:38
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Message;

class StreamReader {

    /**
     * @var Handle
     */
    protected $transport;

    /**
     * @var string
     */
    protected $seen = "";

    /**
     * @var
     */
    protected $message;

    /**
     * @param \trochilidae\Sockets\Handle $transport
     */
    function __construct(Handle $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Consumes data from the read buffer and stores it in an internal buffer
     *
     * @param $length
     *
     * @return string
     */
    public function peek($length){
        $seen = strlen($this->seen);
        if(($toRead = $length - $seen) > 0){
            $this->seen .= $this->transport->read($toRead);
        }
        return substr($this->seen, 0, $length);
    }

    /**
     * Cosumes data from the internal buffer if available otherwise consumes data from the read buffer
     *
     * @param $length
     *
     * @return string
     */
    public function read($length){
        $seen = strlen($this->seen);
        if($length <= $seen){
            $str = substr($this->seen, 0, $length);
            $this->seen = substr($this->seen, $length);
            return $str;
        }
        $str = $this->seen . $this->transport->read($length - $seen);
        $this->seen = "";
        return $str;
    }
} 