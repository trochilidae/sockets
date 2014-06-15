<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/04
 * Time: 18:37
 */

namespace krinfreschi\Stream;


interface ProtocolInterface {

    function read(Resource $resource);

    function write(Resource $resource);

} 