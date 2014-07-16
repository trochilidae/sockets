<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/29
 * Time: 16:19
 */

namespace trochilidae\Sockets\Protocols\Http;


use Guzzle\Http\Message\RequestFactory as GuzzleRequestFactory;

class RequestFactory extends GuzzleRequestFactory
{
    protected $requestClass = 'trochilidae\\Sockets\\Protocols\\Http\\HttpRequest';
} 