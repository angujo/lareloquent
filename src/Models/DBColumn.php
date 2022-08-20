<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\DataType;
use Angujo\Lareloquent\LarEloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Angujo\Lareloquent\str_equal;

class DBColumn
{
    use HasUsage, HasTraits;

    public string      $table_name;
    public string      $column_name;
    public string      $column_comment;
    public int|null    $ordinal_position;
    public string|null $column_default;
    public string      $data_type;
    public bool        $is_nullable;
    public bool        $is_primary;
    public bool        $is_unique;
    public bool        $increments;
    public bool        $is_updating;

    public function isCreatedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->create_columns) && $this->isDateTime();
    }

    public function isUpdatedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->update_columns) && $this->isDateTime();
    }

    public function isDeletedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->soft_delete_columns) && $this->isDateTime();
    }

    public function isDateTime()
    {
        return in_array($this->data_type, ['date_time', 'timestamp', 'datetime']);
    }

    public function docType()
    {
        return $this->dataType().($this->is_nullable ? '|null' : '');
    }

    public function defaultValue()
    {
        return match ($this->PhpDataType()) {
            DataType::DATETIME => str_contains(strtoupper($this->column_default), 'CURRENT_TIMESTAMP') ? null : var_export($this->column_default, true),
            DataType::BOOL => var_export((bool)$this->column_default, true),
            DataType::INT, DataType::FLOAT => $this->column_default,
            default => var_export($this->column_default, true),
        };
    }

    public function dataType()
    {
        return (str_equal(DataType::DATETIME->value, $this->PhpDataType()->value) ? basename(Carbon::class) : $this->PhpDataType()->value);
    }

    public function PhpDataType()
    : DataType
    {
        switch ($this->data_type) {
            case 'tinyint':
                return DataType::BOOL;
            case 'int':
            case 'mediumint':
            case 'bigint':
            case 'smallint':
            case 'year':
                return DataType::INT;
            case 'float':
            case 'double':
            case 'decimal':
                return DataType::FLOAT;
            case 'datetime':
            case 'timestamp':
                return DataType::DATETIME;
            case 'enum':
            case 'json':
            case 'text':
            case 'mediumtext':
            case 'set':
            case 'char':
            case 'binary':
            case 'varbinary':
            case 'blob':
            case 'mediumblob':
            case 'time':
            case 'longblob':
            case 'date':
            case 'geometry':
            case 'varchar':
            case 'longtext':
            default:
                return DataType::STRING;
        }
    }

    protected function setTraits()
    {
        if ($this->isDeletedColumn()) $this->addTrait(basename(SoftDeletes::class));
    }

    protected function setUses()
    {
        switch ($this->data_type) {
            case 'datetime':
            case 'timestamp':
                $this->addUse(Carbon::class);
                break;
        }

        if ($this->isDeletedColumn()) $this->addUse(SoftDeletes::class);
    }
}