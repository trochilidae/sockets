<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 14:30
 */

namespace trochilidae\Sockets\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use trochilidae\Sockets\Connection;
use trochilidae\Sockets\Exceptions\ConnectionException;
use trochilidae\Sockets\ResourceManager;
use trochilidae\Sockets\Handles\DefaultHandle;

class Server extends EventEmitter {

    /**
     * @var \trochilidae\Sockets\Handle
     */
    protected $handle;

    /**
     * @var ResourceManager
     */
    protected $manager;

    protected $connections;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    protected $context = [];

    function __construct(LoopInterface $loop = null, array $context = [])
    {
        $this->manager = new ResourceManager($loop);
        $this->loop = $this->manager->getLoop();
        $this->manager->setEventEmitter($this);
        $this->connections = new \SplObjectStorage();
        $this->context = $context;
        $this->on("disconnect", function(Connection $conn){
            $this->connections->detach($conn);
        });
    }

    public function getResourceManager(){
        return $this->manager;
    }

    public function setProtocols($protocol){
        $this->manager->setProtocols($protocol);
    }

    public function setContext(array $context){
        $this->context = $context;
    }

    /**
     * @param        $port
     * @param string $host
     *
     * @return $this
     * @throws \trochilidae\Sockets\Exceptions\ConnectionException
     */
    public function listen($port, $host = '127.0.0.1'){
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $handle = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $handle) {
            $message = "Could not bind to tcp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }

        $this->handle = new DefaultHandle($handle);
        $this->handle->setNonBlocking();

        $this->loop->addReadStream($handle, function ($handle) {
            $this->handleConnection($handle);
        });

        return $this;
    }

    protected function handleConnection($handle){
        $socket = stream_socket_accept($handle);
        if (false === $socket) {
            $this->emit('error', array(new \RuntimeException('Error accepting new connection')));
            return;
        }
        $this->createConnection($socket);
    }

    /**
     * @return int
     */
    public function getPort()
    {
        $name = stream_socket_get_name($this->handle->get(), false);

        return (int) substr(strrchr($name, ':'), 1);
    }

    /**
     * @param bool $graceful
     */
    public function shutdown($graceful = true)
    {
        $this->loop->removeStream($this->handle->get());
        $this->handle->close();

        foreach($this->connections as $conn){
            /**
             * @var Connection $conn
             */
            $conn->close($graceful);
        }
    }

    /**
     * @param $socket
     *
     * @return Resource
     */
    protected function createConnection($socket)
    {
        $handle = new DefaultHandle($socket);
        $conn = new Connection($handle);
        $this->connections->attach($conn);
        $conn->setServer($this, $this->context);
    }
}