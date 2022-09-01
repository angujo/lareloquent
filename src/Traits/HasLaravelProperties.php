<?php

namespace Angujo\Lareloquent\Traits;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\Factory\ColumnEnum;
use Angujo\Lareloquent\Factory\TraitModel;
use Angujo\Lareloquent\Factory\ValueCast;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use function Angujo\Lareloquent\str_equal;

trait HasLaravelProperties
{

    private function parseColumns()
    : static
    {
        $this->columns = iterator_to_array($this->connection->Columns($this->table->name));
        foreach ($this->columns as $column) {
            if (DataType::DATETIME == $column->PhpDataType()) $this->class->addUse(Carbon::class);
            if ($column->isEnum()) {
                ColumnEnum::Write($column);
                $this->class->addUse($column->enumClass());
            }
            $this->class->getDocBlock()->setTag($column->docPropertyTag());
            if (!isset($this->primaryCol) && $column->is_primary) $this->primaryCol = $column;
            if (LarEloquent::config()->constant_column_names && !is_a($this, TraitModel::class)) {
                $this->class->addConstantFromGenerator($column->constantProperty());
            }

            if (!isset($this->createdCol) && $column->isCreatedColumn()) {
                if (!str_equal('created_at', ($this->createdCol = $column)->column_name)) {
                    $this->class->addConstant('created_at', $this->createdCol->column_name, true);
                }
            }
            if (!isset($this->updatedCol) && $column->isUpdatedColumn()) {
                if (!str_equal('updated_at', ($this->updatedCol = $column)->column_name)) {
                    $this->class->addConstant('updated_at', $this->updatedCol->column_name, true);
                }
            }
            if (!isset($this->deletedCol) && $column->isDeletedColumn()) {
                if (!str_equal('deleted_at', ($this->deletedCol = $column)->column_name)) {
                    $this->class->addConstant('deleted_at', $this->deletedCol->column_name, true);
                }
            }
        }
        return $this;
    }

    private function timestamps()
    : static
    {
        if (!isset($this->createdCol, $this->updatedCol)) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('timestamps', false))
                    ->setDocBlock((new DocBlockGenerator('Indicates if the model should be timestamped.'))
                                      ->setTag((new VarTag('timestamps', 'bool')))));
        }
        return $this;
    }

    private function hidden()
    : static
    {
        if (array_key_exists($this->table->name, LarEloquent::config()->hidden_columns)) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('hidden',
                                       array_values(array_intersect(array_map(fn(DBColumn $column) => $column->column_name, $this->columns), LarEloquent::config()->hidden_columns[$this->table->name]))))
                    ->setDocBlock((new DocBlockGenerator('The attributes that should be hidden for arrays.'))
                                      ->setTag((new VarTag('hidden', 'array')))));
        }
        return $this;
    }

    private function table_name()
    : static
    {
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('table', (LarEloquent::config()->add_table_schema ? $this->connection->dbname.'.' : '').$this->table->name))
                ->setDocBlock((new DocBlockGenerator('Table associated with model.'))
                                  ->setTag((new VarTag('table', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function primary_key()
    : static
    {
        if (!isset($this->primaryCol)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('primaryKey', $this->primaryCol->column_name))
                ->setDocBlock((new DocBlockGenerator('Primary Key associated with model.'))
                                  ->setTag((new VarTag('primaryKey', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        if (!$this->primaryCol->increments) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('incrementing', $this->primaryCol->increments))
                    ->setDocBlock((new DocBlockGenerator('Indicate if Primary Key is auto-incrementing.'))
                                      ->setTag((new VarTag('incrementing', 'bool'))))
                    ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        }
        if (DataType::INT != $this->primaryCol->PhpDataType()) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('keyType', $this->primaryCol->PhpDataType()->value))
                    ->setDocBlock((new DocBlockGenerator('Data type of Primary Key that\'s auto-incrementing.'))
                                      ->setTag((new VarTag('keyType', 'string'))))
                    ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        }
        return $this;
    }

    private function date_format()
    : static
    {
        if (!LarEloquent::config()->date_format) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('dateFormat', LarEloquent::config()->date_format))
                ->setDocBlock((new DocBlockGenerator('The storage format of the model\'s date columns.'))
                                  ->setTag((new VarTag('dateFormat', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function connection()
    : static
    {
        if (!LarEloquent::config()->define_connection) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('connection', $this->connection->name))
                ->setDocBlock((new DocBlockGenerator('The database connection that should be used by the model.'))
                                  ->setTag((new VarTag('connection', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function attributes()
    : static
    {
        if (0 >= (count($defaults = array_filter($this->columns, function(DBColumn $col){ return null !== $col->column_default && null != $col->defaultValue(); })))) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('attributes', array_combine(array_map(fn(DBColumn $col) => $col->column_name, $defaults), array_map(fn(DBColumn $col) => $col->defaultValue(), $defaults))))
                ->setDocBlock((new DocBlockGenerator('The model\'s default values for attributes.'))
                                  ->setTag((new VarTag('attributes', 'array'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function typeCasts()
    : static
    {
        $casts = [];
        foreach ($this->columns as $column) {
            $cast = ValueCast::getCast($column);
            if (empty($cast)) continue;
            if (str_equal('array', $cast)) $cast = AsArrayObject::class;
            if (class_exists($cast)) $this->class->addUse($cast);
            $casts[$column->column_name] = $cast;
        }
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('casts', array_filter($casts)))
                ->setDocBlock((new DocBlockGenerator('The attributes that should be cast.'))
                                  ->setTag((new VarTag('casts', 'array'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

}