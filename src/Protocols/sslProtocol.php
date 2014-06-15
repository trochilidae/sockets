<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/15
 * Time: 16:39
 */

namespace krinfreschi\Stream\Protocols;

use krinfreschi\Stream\Protocol;
use krinfreschi\Stream\Resource;
use krinfreschi\Stream\ResourceManager;

class sslProtocol extends Protocol{

    protected $resources = [];

    protected $storage = [];

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    function read(Resource $resource){
        $this->resources[$resource->getHash()] = $resource;
        //setup if need
        $ret = stream_socket_enable_crypto($resource->getHandle(), STREAM_CRYPTO_METHOD_SSLv23_SERVER);
        if($ret === false){
            //throw an exception
        }else if($ret === 0){
            $this->resources[$resource->getHash()] = $ret;
            $this->resourceManager->read($resource);
            //send back to stream select to return when more data
            return;
        }
        //send to next protocol
        $this->resourceManager->next($resource);
    }

    function write(Resource $resource){

    }


}