<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Enums\SQLType;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use function Angujo\Lareloquent\str_equal;

class ValueCast
{
    private DBColumn $column;
    private ?string  $cast = null;

    /*protected static array $lar_casts = ['array', AsStringable::class, 'boolean', 'collection', 'date', 'datetime',
                                         'immutable_date', 'immutable_datetime', 'double', 'encrypted',
                                         'encrypted:array', 'encrypted:collection', 'encrypted:object', 'float', 'integer',
                                         'object', 'real', 'string', 'timestamp',];

    protected static array $lar_regx_casts = ['/decimal:(\d+)/',];*/


    public function __construct(DBColumn $column)
    {
        $this->column = $column;
    }

    private function defaultCasts()
    {
        return [
            'type:datetime'  => 'datetime:'.(LarEloquent::config()->date_format ?: 'Y-m-d H:i:s'),
            'type:timestamp' => 'datetime:'.(LarEloquent::config()->date_format ?: 'Y-m-d H:i:s'),
            'type:json'      => AsArrayObject::class,
            'type:bool'      => 'boolean',
            'type:boolean'   => 'boolean',
        ];
    }

    public function _getCast()
    : ?string
    {
        if (!empty($this->cast)) return $this->cast;
        $casts = array_merge($this->defaultCasts(), LarEloquent::config()->type_casts);
        foreach ($casts as $type => $cast) {
            if ($this->validType($type) || $this->validColumnName($type)) {
                return $this->cast = $cast;
            }
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