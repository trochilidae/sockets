<?php
/**
 * Created by PhpStorm.
 * User: kito
 * Date: 2014/05/25
 * Time: 20:43
 */

if (!function_exists("dd")) {
    /**
     * @param $_
     */
    function dd($_)
    {
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        die();
    }
}

if (!function_exists("str_replace_last")) {
    function str_replace_last($search, $replace, $str)
    {
        if (($pos = strrpos($str, $search)) !== false) {
            $search_length = strlen($search);
            $str           = substr_replace($str, $replace, $pos, $search_length);
        }

        return $str;
    }
}