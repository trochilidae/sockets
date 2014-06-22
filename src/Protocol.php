<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 17:17
 */

namespace trochilidae\Sockets;

use React\EventLoop\LoopInterface;
use trochilidae\Sockets\Message\MessageBuilder;

abstract class Protocol {

    abstract function onRead(StreamReader $reader, MessageEnvelope $message);

    abstract function onWrite(MessageEnvelope $message);

    abstract function onClose(Resource $resource);

} 