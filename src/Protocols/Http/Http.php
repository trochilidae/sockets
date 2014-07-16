<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/28
 * Time: 18:15
 */

namespace trochilidae\Sockets\Protocols\Http;


use trochilidae\Sockets\Connection;
use trochilidae\Sockets\Exceptions\ProtocolException;
use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;
use trochilidae\Sockets\Protocols\BaseProtocol;
use trochilidae\Sockets\Resource;
use trochilidae\Sockets\StreamReader;

class Http extends BaseProtocol{

    protected $name = "HTTP";

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed
     */
    function onOpen(Resource $resource)
    {
        // TODO: Implement onOpen() method.
    }

    /**
     * @param ReadableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @throws \trochilidae\Sockets\Exceptions\ProtocolException
     * @return bool|mixed|null
     */
    function onRead(ReadableMessageEnvelope $message, Resource $resource)
    {
        if(!$resource instanceof Connection){
            return null;
        }

        $message->readAndStore(1024);
        try {
            $message->save();
            $request = HttpRequest::fromMessage((string)$message);
        } catch (\OverflowException $oe) {
            throw new ProtocolException($resource, new HttpResponse(413));
        }

        return $request;
    }

    /**
     * @param WritableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    function onWrite(WritableMessageEnvelope $message, Resource $resource)
    {

    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    function onClose(Resource $resource)
    {
        // TODO: Implement onClose() method.
    }

}