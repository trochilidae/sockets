<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 21:22
 */

/**
 * @param $handle
 *
 * @return bool
 */
function stream_eof($handle)
{
    return !is_resource($handle) || feof($handle);
}

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