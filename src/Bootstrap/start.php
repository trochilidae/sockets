<?php

/*
|--------------------------------------------------------------------------
| Check Extensions
|--------------------------------------------------------------------------
|
| Laravel requires a few extensions to function. Here we will check the
| loaded extensions to make sure they are present. If not we'll just
| bail from here. Otherwise, Composer will crazily fall back code.
|
*/

if ( ! extension_loaded('mcrypt'))
{
    echo 'Mcrypt PHP extension required.'.PHP_EOL;

    exit(1);
}

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Config\EnvironmentVariables;
use Illuminate\Config\Repository as Config;

/*
|--------------------------------------------------------------------------
| Load The Illuminate Facades
|--------------------------------------------------------------------------
|
| The facades provide a terser static interface over the various parts
| of the application, allowing their methods to be accessed through
| a mixtures of magic methods and facade derivatives. It's slick.
|
*/

Facade::clearResolvedInstances();

Facade::setFacadeApplication($app);

/*
|--------------------------------------------------------------------------
| Register Facade Aliases To Full Classes
|--------------------------------------------------------------------------
|
| By default, we use short keys in the container for each of the core
| pieces of the framework. Here we will register the aliases for a
| list of all of the fully qualified class names making DI easy.
|
*/

$app->registerCoreContainerAliases();

/*
|--------------------------------------------------------------------------
| Register The Environment Variables
|--------------------------------------------------------------------------
|
| Here we will register all of the $_ENV and $_SERVER variables into the
| process so that they're globally available configuration options so
| sensitive configuration information can be swept out of the code.
|
*/

with($envVariables = new EnvironmentVariables(
    $app->getEnvironmentVariablesLoader()))->load($env);

$app->instance('config', $config = new Config(

    $app->getConfigLoader(), $env

));

//if ($env != 'testing') ini_set('display_errors', 'Off');

/*
|--------------------------------------------------------------------------
| Set The Default Timezone
|--------------------------------------------------------------------------
|
| Here we will set the default timezone for PHP. PHP is notoriously mean
| if the timezone is not explicitly set. This will be used by each of
| the PHP date and date-time functions throughout the application.
|
*/

$config = $app['config']['app'];

date_default_timezone_set($config['timezone']);

/*
|--------------------------------------------------------------------------
| Register The Alias Loader
|--------------------------------------------------------------------------
|
| The alias loader is responsible for lazy loading the class aliases setup
| for the application. We will only register it if the "config" service
| is bound in the application since it contains the alias definitions.
|
*/

$aliases = $config['aliases'];

AliasLoader::getInstance($aliases)->register();

/*
|--------------------------------------------------------------------------
| Register The Core Service Providers
|--------------------------------------------------------------------------
|
| The Illuminate core service providers register all of the core pieces
| of the Illuminate framework including session, caching, encryption
| and more. It's simply a convenient wrapper for the registration.
|
*/

$providers = $config['providers'];

$app->getProviderRepository()->load($app, $providers);

$app->booted(function() use ($app, $env){
    
});