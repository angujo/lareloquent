<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBEnum;
use function Angujo\Lareloquent\snake_case;

class TypeScriptEnum extends FileWriter
{
    private DBEnum $dbEnum;
    private string $template = '';

    private function __construct(DBEnum $enum)
    {
        $this->dbEnum = $enum;
        $this->name = $enum->getName();
        $this->dir = LarEloquent::config()->typescript_dir;
        $this->template = "export type {$enum->getName()} = {$enum->tsValue()};";
    }

    private function eType()
    {
        $cases = [];
        foreach ($this->dbEnum->tsCases() as $case) {
            $cases[] = strtoupper(snake_case($case)) . " = '{$case}'";
        }
        return implode(",\n\t", $cases);
    }

    function __toString()
    {
        return $this->template . "\n\nexport enum {$this->dbEnum->getName()}Type {\n\t" . $this->eType() . "\n\n}\n";
    }

    public static function Write(DBEnum $enum)
    {
        (new self($enum))->_write(extension: 'ts');
    }
}
