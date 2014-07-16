<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/28
 * Time: 18:08
 */

namespace trochilidae\Sockets\Protocols;

use trochilidae\Sockets\Exceptions\ProtocolException;
use trochilidae\Sockets\Message\ReadableMessageEnvelope;
use trochilidae\Sockets\Message\WritableMessageEnvelope;
use trochilidae\Sockets\Resource;
use trochilidae\Sockets\StreamReader;

class Ssl extends BaseProtocol {

    protected $name = "SSL";

    protected $requiresHandshake = true;

    protected $error = null;

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    function onOpen(Resource $resource)
    {
        // TODO: Implement onOpen() method.
    }

    /**
     * @param ReadableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @throws \trochilidae\Sockets\Exceptions\ProtocolException
     * @return bool|mixed|string
     */
    function onRead(ReadableMessageEnvelope $message, Resource $resource)
    {
        $this->error = null;
        if (!$resource->secure) {
//            $peak = $message->peek(11); //11 chars read to determine hello packet
            $handle = $resource->getHandle()->get();
            set_error_handler([$this, "error_handler"]);
            $result = stream_socket_enable_crypto($handle, true, STREAM_CRYPTO_METHOD_SSLv23_SERVER);
            restore_error_handler();
            if(!is_null($this->error)){
//                if($this->error["reason"] === "http request"){
//                    return $peak;
//                }
                throw new ProtocolException($resource);
            }else if (0 === $result) {
                return false;
            }

            $resource->secure = true;
            return false;
        }
    }

    /**
     * @param WritableMessageEnvelope $message
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    function onWrite(WritableMessageEnvelope $message, Resource $resource)
    {
        // TODO: Implement onWrite() method.
    }

    /**
     * @param \trochilidae\Sockets\Resource $resource
     * @return mixed|void
     */
    function onClose(Resource $resource)
    {

    }

    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    function error_handler($errno, $errstr, $errfile, $errline){
        //error:[error code]:[library name]:[function name]:[reason string] - https://www.openssl.org/docs/crypto/ERR_error_string.html
        $errorKeys = ["code", "library", "function", "reason"];
        if(str_contains($errstr, "OpenSSL Error messages:")){
            $error = explode(':', substr($errstr, strpos($errstr, "\n") + 1 + 6)); //6 = length of "error:"
            $this->error = array_combine($errorKeys, $error);
        }else{
            $error = explode(':', $errstr);
            $this->error = array_combine($errorKeys, [0, "", "", trim($error[1])]);
        }
    }
}