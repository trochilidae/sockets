<?php

namespace trochilidae\Sockets\Protocols\Http;

use Guzzle\Http\Message\Request;
use trochilidae\Sockets\Message;

/**
 * This class receives streaming data from a client request
 * and parses HTTP headers, returning a Guzzle Request object
 * once it's been buffered
 */
class HttpRequest extends Request implements Message {

    const EOM = "\r\n\r\n";

    /**
     * The maximum number of bytes the request can be
     * This is a security measure to prevent attacks
     * @var int
     */
    public static $maxSize = 4096;

    public static function fromMessage($message) {

        if (strlen($message) > (int)self::$maxSize) {
            throw new \OverflowException("Maximum buffer size of " . self::$maxSize . " exceeded parsing HTTP header");
        }

        $request = RequestFactory::getInstance()->fromMessage($message);

        if(!$request){
            return false;
        }

        return $request;
    }

}