<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\LarEloquent;
use function Angujo\Lareloquent\model_name;

class DBEnum extends DBInterface
{
    public string $table_name;
    public string $column_name;
    public string $column_type;
    public bool   $is_nullable;

    private array $cases = [];

    public function cases()
    {
        if (!empty($this->cases)) return $this->cases;
        $values = json_decode(preg_replace(['/^enum\((.*?)\)$/', '/\'/'], ['[$1]', '"'], $this->column_type), false);
        return $this->cases = (JSON_ERROR_NONE != json_last_error() ? [] : array_combine(array_map('Angujo\Lareloquent\enum_case', $values), $values));
    }

    public function case()
    {
        if (empty($this->cases())) return null;
        return array_values($this->cases)[0];
    }

    public function tsValue()
    {
        return implode(' | ', array_map(fn($v) => "'$v'", array_filter(array_merge($this->cases(), [$this->is_nullable ? 'null' : '']))));
    }

    public function getName()
    : string
    {
        return basename($this->className());
    }

    public function className()
    : string
    {
        return implode('\\', [LarEloquent::config()->enum_namespace, model_name($this->column_name)]);
    }

    public function merge(...$entries)
    : static
    {
        foreach ($entries as $entry) {
            if (!is_array($entry)) continue;
            $this->cases = array_merge($this->cases, $entry);
        }
        return $this;
    }
}
