<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 21:05
 */

namespace trochilidae\Sockets\Transports;

interface TransportInterface {

    /**
     * @return resource
     */
    public function getHandle();

    /**
     * @param $length
     *
     * @return string|bool
     */
    public function read($length);

    /**
     * @param $message
     *
     * @return null|bool|int
     */
    public function write($message);

    /**
     * @return bool
     */
    public function setBlocking();

    /**
     * @return bool
     */
    public function setNonBlocking();

    /**
     * @return bool
     */
    public function isReadable();

    /**
     * @return bool
     */
    public function isWritable();

    /**
     * @return bool
     */
    public function isClosed();

    /**
     * @return bool
     */
    public function isSync();

}