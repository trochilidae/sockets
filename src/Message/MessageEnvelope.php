<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/22
 * Time: 16:45
 */

namespace trochilidae\Sockets\Message;

use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use trochilidae\Sockets\Message;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Protocols\ProtocolIteratorWrap;
use trochilidae\Sockets\Resource;

abstract class MessageEnvelope implements Message
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * @var \trochilidae\Sockets\Resource
     */
    protected $resource;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param Protocol $protocol
     * @param string $message
     * @return static
     */
    public static function make(Resource $resource, Protocol $protocol, $message = ""){

    }

    /**
     * @return \trochilidae\Sockets\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return Protocol
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param Message|string $message
     *
     * @throws InvalidArgumentException
     */
    public function setMessage($message)
    {
        if (!is_string($message) && !($message instanceof Message)) {
            throw new InvalidArgumentException();
        }
        if (is_string($message)) {
            $message = new PlainMessage($message);
        }
        $this->message = $message;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        if (is_null($this->message)) {
            $this->message = new PlainMessage();
        }

        return $this->message;
    }

    /**
     * @return string
     */
    function __toString()
    {
        return (string)$this->message;
    }

}