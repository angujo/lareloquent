<?php

namespace Angujo\Lareloquent\Factory;

use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\str_equal;

class ValueGen extends ValueGenerator
{
    public const TYPE_ASIS = 'asis';

    public function generate()
    : string
    {
        if (str_equal($this->type, self::TYPE_ASIS)) return $this->value;
        return parent::generate();
    }

}