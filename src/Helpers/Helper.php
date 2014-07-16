<?php

if (!function_exists("str_replace_last")) {

    /**
     * @param $search
     * @param $replace
     * @param $str
     *
     * @return mixed
     */
    function str_replace_last($search, $replace, $str)
    {
        if (($pos = strrpos($str, $search)) !== false) {
            $search_length = strlen($search);
            $str           = substr_replace($str, $replace, $pos, $search_length);
        }

        return $str;
    }

}

if(!function_exists("get_calling_class")){

    function get_calling_class($instance = false){
        //get the trace
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i])) // is it set?
                if ($class != $trace[$i]['class']) {
                    if($instance === true){
                        return $trace[$i]['object'];
                    }
                    return $trace[$i]['class'];
                } // is it a different class
        }
    }

}

if(!function_exists("get_real_class")){

    function get_real_class($obj) {
        $classname = get_class($obj);

        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
            $classname = $matches[1];
        }

        return $classname;
    }

}