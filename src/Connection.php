<?php

namespace trochilidae\Sockets;

use trochilidae\Sockets\Socket\Server;

class Connection extends Resource {

    /**
     * @param Handle $handle
     */
    function __construct($handle)
    {
        parent::__construct($handle);
    }

    /**
     * @param Server $server
     * @param array $context
     */
    public function setServer(Server $server, array $context = null){
        $this->_setResourceManager($server->getResourceManager(), $context);
    }

} 