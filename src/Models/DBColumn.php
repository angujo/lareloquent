<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\LarEloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class DBColumn
{
    use HasUsage, HasTraits;

    public string      $table_name;
    public string      $column_name;
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

    public function dataType()
    {
        return $this->PhpDataType().($this->is_nullable ? '|null' : '');
    }

    public function PhpDataType()
    {
        switch ($this->data_type) {
            case 'int':
            case 'mediumint':
            case 'tinyint':
            case 'bigint':
            case 'smallint':
            case 'year':
                return 'int';
            case 'float':
            case 'double':
            case 'decimal':
                return 'float';
            case 'datetime':
            case 'timestamp':
                return basename(Carbon::class);
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
                return 'string';
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