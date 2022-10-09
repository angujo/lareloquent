<?php

namespace Angujo\Lareloquent {

    use Angujo\Lareloquent\Models\GeneralTag;
    use plejus\PhpPluralize\Inflector;

    define('BASE_DIR', dirname(__FILE__));
    define('ROOT_DIR', dirname(__FILE__, 2));

    if (!function_exists('flatten_array')) {
        function flatten_array(array $items, array $flattened = [])
        : array
        {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $flattened = flatten_array($item, $flattened);
                    continue;
                }
                $flattened[] = $item;
            }
            return $flattened;
        }
    }

    if (!function_exists('model_name')) {
        function model_name(string $txt)
        : string
        {
            return preg_replace_callback('/([A-Z])([a-z\d]+)$/',
                function($matches){ return in_singular($matches[1].$matches[2]); },
                                         preg_replace_callback('/(^|[^A-Za-z\d])([a-zA-Z])([A-Z]+|[a-z\d]+)/', function($matches){ return strtoupper($matches[2]).strtolower($matches[3]); }, $txt));
        }
    }

    if (!function_exists('snake_case')) {
        function snake_case(string $txt)
        : string
        {
            return preg_replace_callback('/([^a-z\d])([a-z\d]+)/', function($matches){ return '_'.strtolower($matches[1]).strtolower($matches[2]); }, $txt);
        }
    }

    if (!function_exists('col_name_reference')) {
        function col_name_reference(string $txt)
        : string
        {
            return preg_replace('/_id(\s+)?$/', '', $txt);
        }
    }

    if (!function_exists('enum_case')) {
        function enum_case(string $txt)
        : string
        {
            return strtoupper(preg_replace(['/^[^a-z_]+/', '/[^a-z\d_]+/'], ['_', '_'], strtolower($txt)));
        }
    }

    if (!function_exists('number_hash')) {
        function number_hash(string $str, int $base = 10)
        : string
        {
            return (unpack("L", hash('sha256', $str, true)) [1] % $base) + 1; // get 1 - 10 value
        }
    }

    if (!function_exists('method_name')) {
        function method_name(string $txt)
        : string
        {
            return preg_replace_callback('/^([a-zA-Z])/',
                function($matches){ return strtolower($matches[1]); },
                                         preg_replace_callback(
                                             '/(^|[^A-Za-z\d])([a-zA-Z\d])([A-Z]+|[a-z\d]+)/',
                                             function($matches){ return strtoupper($matches[2]).strtolower($matches[3]); },
                                             $txt));
        }
    }

    if (!function_exists('str_rand')) {
        function str_rand(int $length = 5, $upper = true, $lower = true, $numbers = true, $special_xters = true)
        : string
        {
            if ($length <= 0) return '';
            $alphal = 'abcdefghijklmnopqrstuvwxyz';
            $alphau = strtoupper($alphal);
            $nums   = '0123456789';
            $specs  = '!.-_@$%*()';

            $xters = [];
            if ($upper) $xters[] = $alphau;
            if ($lower) $xters[] = $alphal;
            if ($numbers) $xters[] = $nums;
            if ($special_xters) $xters[] = $specs;
            $xters = implode('', $xters);
            return $xters[mt_rand(0, strlen($xters) - 1)].str_rand($length - 1, $upper, $lower, $numbers, $special_xters);
        }
    }

    if (!function_exists('model_file')) {
        function model_file(string $txt, string|null $ext = 'php')
        : string
        {
            return model_name($txt).'.'.($ext && is_string($ext) ? $ext : 'php');
        }
    }

    if (!function_exists('tag')) {
        function tag(string $name, string $description)
        : GeneralTag
        {
            return (new GeneralTag(name: $name, description: $description));
        }
    }

    if (!function_exists('in_singular')) {
        function in_singular(string $word)
        : string
        {
            $infl = new Inflector();
            return !preg_match('/([^a-z])(.*?)$/', $word) ? $infl->singular($word) :
                preg_replace_callback('/([^a-z])(.*?)$/', function($matches) use ($infl){
                    return $matches[1].($infl->isSingular($matches[2]) ? $matches[2] : $infl->singular($matches[2]));
                },                    $word);
        }
    }

    if (!function_exists('model_file')) {
        function in_plural(string $word, float $count = 99)
        : string
        {
            $infl = new Inflector();
            return !preg_match('/([^a-z])(.*?)$/', $word) && !$infl->isPlural($word) ? $infl->pluralize($word, $count) :
                preg_replace_callback('/([^a-z])(.*?)$/', function($matches) use ($infl, $count){
                    return $matches[1].($infl->isPlural($matches[2]) ? $matches[2] : $infl->pluralize($matches[2], $count));
                },                    $word);
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


