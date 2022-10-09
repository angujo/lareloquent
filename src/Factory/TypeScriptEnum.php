<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBEnum;

class TypeScriptEnum extends FileWriter
{
    private string $template = '';

    private function __construct(DBEnum $enum)
    {
        $this->name     = $enum->getName();
        $this->dir      = LarEloquent::config()->typescript_dir;
        $this->template = "export type {$enum->getName()} = {$enum->tsValue()};";
    }

    function __toString()
    {
        return $this->template;
    }

    public static function Write(DBEnum $enum)
    {
        (new self($enum))->_write(extension: 'ts');
    }
}
