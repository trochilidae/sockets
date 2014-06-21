<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/01
 * Time: 22:34
 */

namespace trochilidae\Sockets;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class Application extends Container
{

    const VERSION = '0.1-dev';

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    function __construct(LoopInterface $loop = null)
    {
        if (is_null($loop)) {
            $loop = Factory::create();
        }

        $this->loop = $loop;

        $this->registerCoreContainerAliases();

        $app = &$this;

        $this->instance("app", $app);
        $this->instance("loop", $loop);
        $this->instance("events", new Dispatcher($this));

        $this->bind("resource", function($container, $parameters) use ($app){
            return $app->make('trochilidae\Sockets\Resource', $parameters);
        });
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases(){
        $aliases = [
            "app" => 'trochilidae\Sockets\Application',
            "loop"  => 'React\EventLoop\LoopInterface',
            "server" => 'trochilidae\Sockets\Socket\Server',
            "client" => 'trochilidae\Sockets\Socket\Client',
            "socket.server" => 'trochilidae\Sockets\Socket\Server',
            "socket.client" => 'trochilidae\Sockets\Socket\Client',
            "events" => 'Illuminate\Events\Dispatcher'
        ];

        foreach($aliases as $key => $alias){
            $this->alias($key, $alias);
        }
    }

    public function run(){
        $this->loop->run();
    }

    /**
     * Dynamically access application services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set application services.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

}