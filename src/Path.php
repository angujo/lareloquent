<?php

namespace Angujo\Lareloquent;

class Path
{
    public static string $BASE = BASE_DIR;
    public static string $ROOT = ROOT_DIR;

    public static function Combine(...$paths)
    {
        return implode(DIRECTORY_SEPARATOR, array_filter($paths));
    }

    public static function Template(string $file_name)
    : string
    {
        return self::Combine(Path::$BASE, 'templates', $file_name);
    }
}