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

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param LoopInterface $loop
     */
    function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    abstract function onRead(Resource $resource, MessageBuilder $message);

    abstract function onWrite(Resource $resource, $message);

    abstract function onClose(Resource $resource);

} 