<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/22
 * Time: 16:45
 */

namespace trochilidae\Sockets;

use trochilidae\Sockets\Exceptions\InvalidArgumentException;
use trochilidae\Sockets\Message\PlainMessage;

class MessageEnvelope implements Message
{

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var ProtocolList
     */
    protected $protocols;

    /**
     * @var \trochilidae\Sockets\Resource
     */
    protected $resource;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param ProtocolList                  $protocols
     */
    function __construct(Resource $resource, ProtocolList $protocols)
    {
        $protocols->rewind();
        $this->protocols = $protocols;
        $this->resource  = $resource;
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @param string                        $defaultMessage
     * @param Protocol                      $currentProtocol
     *
     * @return MessageEnvelope
     */
    public static function make(Resource $resource, $defaultMessage = "", Protocol $currentProtocol = null)
    {
        $protocols = clone $resource->getProtocols();
        if (!is_null($currentProtocol)) {
            $protocols = $protocols->filterByProtocol($currentProtocol);
        }
        $protocols->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO);
        $protocols->rewind();
        $self = new self($resource, $protocols);
        $self->setMessage($defaultMessage);

        return $self;
    }

    /**
     * @return \trochilidae\Sockets\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return \SplDoublyLinkedList
     */
    public function getProtocols()
    {
        return $this->protocols;
    }

//    /**
//     * (PHP 5 &gt;= 5.3.0)<br/>
//     * Sets the mode of iteration
//     * @link http://php.net/manual/en/spldoublylinkedlist.setiteratormode.php
//     * @param int $mode <p>
//     * There are two orthogonal sets of modes that can be set:
//     * </p>
//     * The direction of the iteration (either one or the other):
//     * <b>SplDoublyLinkedList::IT_MODE_LIFO</b> (Stack style)
//     * @return void
//     */
//    public function setIteratorMode ($mode) {
//        $this->protocols->setIteratorMode($mode);
//    }

    /**
     * @param Message|string $message
     *
     * @throws Exceptions\InvalidArgumentException
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
     * @param $message
     */
    public function append($message)
    {
        $message = (string)$this->message . $message;
        $this->setMessage($message);
    }

    /**
     * @return string
     */
    function __toString()
    {
        return (string)$this->message;
    }
}