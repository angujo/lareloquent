<?php

namespace Angujo\Lareloquent;

class Path
{
    public static string $BASE = BASE_DIR;
    public static string $ROOT = ROOT_DIR;

    public static function Combine(...$paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}