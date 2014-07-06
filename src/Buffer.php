<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/06
 * Time: 15:47
 */
namespace trochilidae\Sockets;

interface Buffer
{
    /**
     * @param Handle $handle
     * @param        $message
     *
     * @return bool
     */
    public function unshift(Handle $handle, $message);

    /**
     * @param array $messages
     *
     * @return int
     */
    public function getSize(array &$messages);

    /**
     * @param Handle $handle
     *
     * @return mixed|null
     */
    public function pop(Handle $handle);

    /**
     * @param Handle $handle
     * @param string $message
     *
     * @return bool
     */
    public function write(Handle $handle, $message);

    /**
     * @param Handle $handle
     *
     * @return array
     */
    public function pull(Handle $handle);

    /**
     * @param Handle $handle
     * @param array  $messages
     */
    public function set(Handle $handle, array $messages = []);

    /**
     * @param Handle $handle
     *
     * @return bool
     */
    public function isEmpty(Handle $handle);
}