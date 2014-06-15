<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/06/08
 * Time: 12:47
 */

namespace krinfreschi\Stream\Support;


trait ObjectTrait {

    protected static $identifiers = [];

    protected $id;

    public function getHash(){
        if(is_null($this->id)){
            $id = sha1(spl_object_hash($this));
            if(!array_key_exists($id, static::$identifiers)){
                $this->id = $id;
                static::$identifiers[$this->id] = true;
            }else{
                return $this->getHash();
            }
        }
        return $this->id;
    }

    function __destruct()
    {
        if($this->id && isset(static::$identifiers[$this->id])){
            unset(static::$identifiers[$this->id]);
        }
    }


} 