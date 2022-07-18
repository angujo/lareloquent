<?php

namespace Angujo\Lareloquent {
    define('BASE_DIR', dirname(__FILE__));
    define('ROOT_DIR', dirname(__FILE__, 2));

    enum Framework
    {
        case LARAVEL;
        case CUSTOM;
    }

    if (!function_exists('model_name')) {
        function model_name(string $txt)
        : string
        {
            return preg_replace_callback(
                '/(^|[^A-Za-z\d])([a-zA-Z])([A-Z]+|[a-z\d]+)/',
                function($matches){ return strtoupper($matches[2]).strtolower($matches[3]); },
                $txt);
        }
    }

    if (!function_exists('model_file')) {
        function model_file(string $txt)
        : string
        {
            return model_name($txt).'.php';
        }
    }

    if (!function_exists('str_equal')) {
        function str_equal(string $str1, string $str2)
        : bool
        {
            return 0 === strcasecmp($str1, $str2);
        }
    }
}


