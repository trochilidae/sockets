<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 14:30
 */

namespace trochilidae\Sockets\Socket;

use Evenement\EventEmitter;
use Illuminate\Support\Facades\Event;
use React\EventLoop\LoopInterface;
use trochilidae\Sockets\Connection;
use trochilidae\Sockets\Exceptions\ConnectionException;
use trochilidae\Sockets\Resource;
use trochilidae\Sockets\ResourceManager;
use trochilidae\Sockets\Handles\DefaultHandle;

class Server extends EventEmitter {

    /**
     * @var \trochilidae\Sockets\Handle
     */
    protected $transport;

    /**
     * @var ResourceManager
     */
    protected $manager;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    function __construct(LoopInterface $loop = null)
    {
        $this->manager = new ResourceManager($loop);
        $this->loop = $this->manager->getLoop();
        $this->manager->setEventEmitter($this);
    }

    public function setProtocols($protocol){
        $this->manager->setProtocols($protocol);
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

        $this->transport = new DefaultHandle($handle);
        $this->transport->setNonBlocking();

        $this->loop->addReadStream($handle, function ($handle) {
            $newSocket = stream_socket_accept($handle);
            if (false === $newSocket) {
                $this->emit('error', array(new \RuntimeException('Error accepting new connection')));
                return;
            }
            $this->handleConnection($newSocket);
        });

        return $this;
    }

    public function handleConnection($socket)
    {
        $this->transport->setNonBlocking();

        $client = $this->createConnection($socket);

        $this->emit('connection', array($client));
    }

    /**
     * @return int
     */
    public function getPort()
    {
        $name = stream_socket_get_name($this->transport->getHandle(), false);

        return (int) substr(strrchr($name, ':'), 1);
    }

    /**
     *
     */
    public function shutdown()
    {
//        $this->loop->removeStream($this->transport->getHandle());
//        $this->transport->close();
//        $transport = $this->transport;
//        $this->loop->nextTick(function(LoopInterface $loop) use($transport){
//            $loop->removeStream($transport->getHandle());
//            $transport->close();
//        });
    }

    /**
     * @param $socket
     *
     * @return Resource
     */
    public function createConnection($socket)
    {
        $handle = new DefaultHandle($socket);
        $conn = new Connection($handle);
        $conn->setResourceManager($this->manager);
    }
}