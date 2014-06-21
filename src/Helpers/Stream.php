<?php

if(!function_exists("stream_eof")){

    /**
     * @param $handle
     *
     * @return bool
     */
    function stream_eof($handle)
    {
        return !is_resource($handle) || feof($handle);
    }

}

if(!function_exists("stream_closed")){

    /**
     * @param $handle
     *
     * @return bool
     */
    function stream_closed($handle)
    {
        return !is_resource($handle);
    }

}

if(!function_exists("stream_get_access")){

    /**
     * @param $handle
     *
     * @param array $meta
     *
     * @return array|bool
     */
    function stream_get_access($handle, array $meta = array())
    {
        if (stream_eof($handle)) {
            return false;
        }

        if(empty($meta)){
            $meta   = stream_get_meta_data($handle);
        }

        $access = array("read" => false, "write" => false);
        switch ($meta["mode"]) {
            case "r":
                $access["read"] = true;
                break;
            case "w":
            case "a":
            case "x":
            case "c":
                $access["write"] = true;
                break;
            case "r+":
            case "w+":
            case "a+":
            case "x+":
            case "c+":
                $access["read"]  = true;
                $access["write"] = true;
                break;
        }

        return $access;
    }

}

