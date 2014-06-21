<?php

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