<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/15
 * Time: 16:39
 */

namespace trochilidae\Sockets\Protocols;

use trochilidae\Sockets\Message\MessageBuilder;
use trochilidae\Sockets\Protocol;
use trochilidae\Sockets\Resource;

class sslProtocol extends Protocol
{

    function onRead(Resource $resource, MessageBuilder $message)
    {
        $ret = stream_socket_enable_crypto($resource->getHandle(), STREAM_CRYPTO_METHOD_SSLv23_SERVER);

        if ($ret === false) {
            throw new \Exception("Unable to make secure");
        } else if ($ret === 0) {
            return false;
        }

        return $resource;
    }

}