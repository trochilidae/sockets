<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/13
 * Time: 22:58
 */
namespace trochilidae\Sockets;

use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;

interface Protocol
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function requiresHandshake();

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    public function onOpen(Resource $resource);

    /**
     * @param WritableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    public function onWrite(WritableMessageEnvelope $message, Resource $resource);

    /**
     * @param \trochilidae\Sockets\Resource $resource
     *
     * @return mixed
     */
    public function onClose(Resource $resource);

    /**
     * @param ReadableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    public function onRead(ReadableMessageEnvelope $message, Resource $resource);

}