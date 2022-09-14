<?php

namespace Angujo\Lareloquent {

    use Angujo\Lareloquent\Models\GeneralTag;
    use plejus\PhpPluralize\Inflector;

    define('BASE_DIR', dirname(__FILE__));
    define('ROOT_DIR', dirname(__FILE__, 2));

    if (!function_exists('flatten_array')) {
        function flatten_array(array $items, array $flattened = [])
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

    if (!function_exists('method_name')) {
        function method_name(string $txt)
        : string
        {
            return preg_replace_callback('/^([a-zA-Z])/',
                function($matches){ return strtolower($matches[1]); },
                                         preg_replace_callback(
                                             '/(^|[^A-Za-z\d])([a-zA-Z])([A-Z]+|[a-z\d]+)/',
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
        function model_file(string $txt, bool $base = false)
        : string
        {
            return model_name(($base ? LarEloquent::config()->base_abstract_prefix.'_' : '').$txt).'.php';
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
            return $infl->isSingular($word) ? $word : $infl->singular($word);
        }
    }

    if (!function_exists('model_file')) {
        function in_plural(string $word, float $count = 99)
        : string
        {
            $infl = new Inflector();
            return $infl->isPlural($word) ? $word : $infl->pluralize($word, $count);
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


