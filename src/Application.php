<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/01
 * Time: 22:34
 */

namespace krinfreschi\Stream;

use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class Application extends Container
{

    const VERSION = '0.1-dev';

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = array();

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = array();

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = array();

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = array();

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = array();

    protected $bootstrap;

    /**
     * @var string
     */
    protected $env;

    function __construct($env = "production", LoopInterface $loop = null)
    {
        if (is_null($loop)) {
            $loop = Factory::create();
        }

        $this->loop = $loop;

        $this->bootstrap = __DIR__ . "/Bootstrap/start.php";

//        $app = &$this;
//
//        $this->bind("resource", function($container, $parameters) use ($app){
//            return $app->make('krinfreschi\Stream\Resource', $parameters);
//        });
        $this->env = $env;
        $this['path'] = getcwd();
        $this->boot();
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases(){
        $aliases = [
            "app" => 'krinfreschi\Stream\Application',
            "loop"  => 'React\EventLoop\LoopInterface',
            "server" => 'krinfreschi\Stream\Socket\Server',
            "client" => 'krinfreschi\Stream\Socket\Client',
            "socket.server" => 'krinfreschi\Stream\Socket\Server',
            "socket.client" => 'krinfreschi\Stream\Socket\Client'
        ];

        foreach($aliases as $key => $alias){
            $this->alias($key, $alias);
        }
    }

    /**
     * Get the service provider repository instance.
     *
     * @return \Illuminate\Foundation\ProviderRepository
     */
    public function getProviderRepository()
    {
        $manifest = $this['config']['app.manifest'];

        return new ProviderRepository(new Filesystem, $manifest);
    }

    /**
     * Get the configuration loader instance.
     *
     * @return \Illuminate\Config\LoaderInterface
     */
    public function getConfigLoader()
    {
        return new FileLoader(new Filesystem, $this['path'].'/config');
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }


    /**
     * Force register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @return \Illuminate\Support\ServiceProvider
     */
    public function forceRegister($provider, $options = array())
    {
        return $this->register($provider, $options, true);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = array(), $force = false)
    {
        if ($registered = $this->getRegistered($provider) && ! $force)
            return $registered;

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider))
        {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value)
        {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by the developer's application logics.
        if ($this->booted) $provider->boot();

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getRegistered($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        if (array_key_exists($name, $this->loadedProviders))
        {
            return array_first($this->serviceProviders, function($key, $value) use ($name)
            {
                return get_class($value) == $name;
            });
        }
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Illuminate\Support\ServiceProvider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $class = get_class($provider);
//        $this['events']->fire($class = get_class($provider), array($provider));

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider)
        {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = array();
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
    protected function loadDeferredProvider($service)
    {
        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if ( ! isset($this->loadedProviders[$provider]))
        {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service) unset($this->deferredServices[$service]);

        $this->register($instance = new $provider($this));

        if ( ! $this->booted)
        {
            $this->booting(function() use ($instance)
            {
                $instance->boot();
            });
        }
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param array $callbacks
     *
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback)
        {
            call_user_func($callback, $this);
        }
    }


    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) $this->fireAppCallbacks(array($callback));
    }

    /**
     * Boot the application and fire app callbacks.
     *
     * @return void
     */
    protected function bootApplication()
    {
        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) return;

        $this->bootstrap();

        array_walk($this->serviceProviders, function($p) { $p->boot(); });

        $this->bootApplication();
    }

    protected function bootstrap(){

        $this->instance('app', $this);
        $this->instance("loop", $this->loop);
        $env = $this->env;

        $app = $this;
        $boostrap = function() use($app, $env){
            require_once $this->bootstrap;
        };
        $boostrap();
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