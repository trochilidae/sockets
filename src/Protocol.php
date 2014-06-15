<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 17:17
 */

namespace krinfreschi\Stream;

use React\EventLoop\LoopInterface;

abstract class Protocol implements ProtocolInterface {

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

} 