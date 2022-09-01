<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\Factory\ValueCast;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Enums\SQLType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class DBColumn
{
    use HasUsage, HasTraits;

    public string      $table_name;
    public string      $column_name;
    public string|null $referenced_table_name;
    public string|null $column_type;
    public string|null $referenced_column_name;
    public string      $column_comment;
    public int|null    $ordinal_position;
    public int|null    $character_maximum_length = null;
    public string|null $column_default;
    public string      $data_type;
    public bool        $is_nullable;
    public bool        $is_primary;
    public bool        $is_unique;
    public bool        $increments;
    public bool        $is_updating;

    private ValueCast|null $valueCast = null;

    public function cast()
    {
        return ($this->valueCast ?? ($this->valueCast = new ValueCast($this)))->_getCast();
    }

    public function isEnum()
    {
        return str_equal($this->data_type, SQLType::ENUM->value);
    }

    public function enumClass()
    {
        return implode('\\', [LarEloquent::config()->enum_namespace, model_name($this->table_name.'_'.$this->column_name)]);
    }

    public function constantName()
    {
        return strtoupper(LarEloquent::config()->constant_column_prefix.preg_replace(['/(^[^a-zA-Z]+)|([^a-zA-Z\d]+$)/', '/[^a-zA-Z0-9]+/'], ['', '_'], $this->column_name));
    }

    public function constantProperty()
    {
        return (new PropertyGenerator($this->constantName(), new PropertyValueGenerator($this->column_name), PropertyGenerator::FLAG_CONSTANT | AbstractMemberGenerator::FLAG_FINAL));
    }

    public function docPropertyTag()
    {

        if (!empty($this->cast()) && class_exists($this->cast())) {
            $types = [$this->cast()];
        } else $types = [$this->isEnum() ? basename($this->enumClass()) : $this->dataType()];
        if ($this->is_nullable) $types[] = 'null';
        return (new PropertyTag($this->column_name))
            ->setTypes($types)
            ->setDescription($this->column_comment);
    }

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
            DataType::DATETIME => str_contains(strtoupper($this->column_default), 'CURRENT_TIMESTAMP') ? null : $this->column_default,
            DataType::BOOL => (bool)$this->column_default,
            DataType::INT => intval($this->column_default),
            DataType::FLOAT => floatval($this->column_default),
            default => $this->column_default,
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
            case SQLType::TINYINT->value:
                return DataType::BOOL;
            case SQLType::INT->value:
            case SQLType::MEDIUMINT->value:
            case SQLType::BIGINT->value:
            case SQLType::SMALLINT->value:
            case SQLType::YEAR->value:
                return DataType::INT;
            case SQLType::FLOAT->value:
            case SQLType::DOUBLE->value:
            case SQLType::DECIMAL->value:
                return DataType::FLOAT;
            case SQLType::DATETIME->value:
            case SQLType::TIMESTAMP->value:
                return DataType::DATETIME;
            case SQLType::ENUM->value:
            case SQLType::JSON->value:
            case SQLType::TEXT->value:
            case SQLType::MEDIUMTEXT->value:
            case SQLType::SET->value:
            case SQLType::CHAR->value:
            case SQLType::BINARY->value:
            case SQLType::VARBINARY->value:
            case SQLType::BLOB->value:
            case SQLType::MEDIUMBLOB->value:
            case SQLType::TIME->value:
            case SQLType::LONGBLOB->value:
            case SQLType::DATE->value:
            case SQLType::GEOMETRY->value:
            case SQLType::VARCHAR->value:
            case SQLType::LONGTEXT->value:
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
            case SQLType::DATETIME->value:
            case SQLType::TIMESTAMP->value:
                $this->addUse(Carbon::class);
                break;
        }

        if ($this->isDeletedColumn()) $this->addUse(SoftDeletes::class);
    }
}