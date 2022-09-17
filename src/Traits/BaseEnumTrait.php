<?php

namespace Angujo\Lareloquent\Traits;

use function Angujo\Lareloquent\str_equal;

trait BaseEnumTrait
{
    public static function fromValue(string $value)
    : self
    {
        foreach (self::cases() as $case) {
            if (str_equal($value, $case->value)) return $case;
        }
        return self::NONE;
    }
}