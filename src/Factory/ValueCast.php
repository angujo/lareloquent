<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\SQLType;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use function Angujo\Lareloquent\str_equal;

class ValueCast
{
    private string   $data_type;
    private DBColumn $column;

    protected static array $lar_casts = ['array', AsStringable::class, 'boolean', 'collection', 'date', 'datetime',
                                         'immutable_date', 'immutable_datetime', 'double', 'encrypted',
                                         'encrypted:array', 'encrypted:collection', 'encrypted:object', 'float', 'integer',
                                         'object', 'real', 'string', 'timestamp',];

    protected static array $lar_regx_casts = ['/decimal:(\d+)/',];

    public function __construct(DBColumn $column)
    {
        $this->column = $column;
    }

    private function _getCast()
    {
        foreach (LarEloquent::config()->type_casts as $type => $cast) {
            if ($this->validType($type) || $this->validColumnName($type)) {
                return  $cast;
            }
        }
        return null;
    }

    public function locate()
    {
        if (in_array(strtolower($this->data_type), array_map('strtolower', self::$lar_casts))) return $this->data_type;
        foreach (self::$lar_regx_casts as $lar_regx_cast) {
            if (preg_match($lar_regx_cast, $this->data_type)) return $this->data_type;
        }
        return null;
    }

    private function validColumnName(string $check)
    {
        $regex = preg_replace(['/^(%)+/', '/(%)+$/', '/(%)/'], ['^(.*?)', '(.*?)$', '(.*?)'], $check);
        return 1 === preg_match("/{$regex}/", $this->column->column_name);
    }

    private function validType(string $type)
    {
        if (!str_starts_with($type, 'type:')) return false;
        $type = preg_replace('/^type:/', '', $type);
        return (in_array(strtolower($type), array_map(fn(SQLType $t) => $t->value, SQLType::cases())) && str_equal($this->column->data_type, $type));
    }

    public static function getCast(DBColumn $column)
    {
        return (new self($column))->_getCast();
}
}