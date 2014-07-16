<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/07/15
 * Time: 23:20
 */

namespace trochilidae\Sockets\Protocols;


use trochilidae\Sockets\Protocols\Http\Http;

class Https extends ProtocolGroup {

    function __construct()
    {
        parent::__construct();
        $this->protocols = [new Ssl(), new Http()];
    }

    /**
     * @return bool
     */
    public function requiresHandshake()
    {
        return true;
    }
}