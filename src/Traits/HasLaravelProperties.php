<?php

namespace Angujo\Lareloquent\Traits;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\Enums\RecursiveMethod;
use Angujo\Lareloquent\Factory\ColumnEnum;
use Angujo\Lareloquent\Factory\TraitModel;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\DocBlock\Tag\MethodTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

trait HasLaravelProperties
{

    private function parseColumns(): static
    {
        $this->columns = iterator_to_array($this->connection->Columns($this->table->name));
        foreach ($this->columns as $column) {
            if (DataType::DATETIME == $column->PhpDataType()) $this->class->addUse(Carbon::class);
            if ($column->isEnum()) {
                $this->class->addUse($column->getEnum()?->className());
            }
            $this->class->getDocBlock()->setTag($column->docPropertyTag())->setWordWrap(false);
            if (!isset($this->primaryCol) && ($column->is_primary || ($this->table->is_view && str_equal(LarEloquent::config()->primary_key_name, $column->column_name)))) $this->primaryCol = $column;
            if (LarEloquent::config()->constant_column_names && !is_a($this, TraitModel::class) && !$this->class->hasConstant($column->constantName())) {
                $this->class->addConstantFromGenerator($column->constantProperty());
            }

            if (!isset($this->createdCol) && $column->isCreatedColumn()) {
                if (!str_equal('created_at', ($this->createdCol = $column)->column_name)) {
                    $this->class->addConstant('CREATED_AT', $this->createdCol->column_name, true);
                }
            }
            if ($column->isParentColumn()) {
                $this->has_recursives = true;
                $this->class->addUse(HasRecursiveRelationships::class)
                    ->addTrait('HasRecursiveRelationships')
                    ->addUse(implode('\\', [LarEloquent::config()->namespace, model_name($this->table_name)]));
                foreach (RecursiveMethod::cases() as $method) {
                    $this->class->getDocBlock()->setTag($this->recursivePropertyTag($method));
                }
                if (!str_equal('parent_id', $column->column_name)) $this->class->addMethodFromGenerator($this->parentKeyMethod($column->column_name));
            }
            if (!isset($this->updatedCol) && $column->isUpdatedColumn()) {
                if (!str_equal('updated_at', ($this->updatedCol = $column)->column_name)) {
                    $this->class->addConstant('UPDATED_AT', $this->updatedCol->column_name, true);
                }
            }
            if (!$this->table->is_view && !isset($this->deletedCol) && $column->isDeletedColumn()) {
                if (!str_equal('deleted_at', ($this->deletedCol = $column)->column_name)) {
                    $this->class->addConstant('DELETED_AT', $this->deletedCol->column_name, true);
                }
                $this->class->addUse(SoftDeletes::class)->addTrait('SoftDeletes');
            }
        }
        return $this;
    }


    private function recursivePropertyTag(RecursiveMethod $method)
    {
        $types = [model_name($this->table_name) . ($method->isCollection() ? '[]' : '')];
        if ($method->isCollection()) $types[] = 'Collection';
        return (new PropertyTag($method->value))
            ->setTypes($types)
            ->setDescription($method->description());
    }

    private function parentKeyMethod($column_name)
    {
        return (new MethodGenerator('getParentKeyName'))
            ->setDocBlock((new DocBlockGenerator('Get column name for the parent key column!'))->setTag(new ReturnTag(['string'])))
            ->setFlags([AbstractMemberGenerator::FLAG_PUBLIC])
            ->setBody("return '$column_name';");
    }

    private function localScopes()
    {
        if (!(is_array(LarEloquent::config()->local_scopes) && array_key_exists($this->table->name, LarEloquent::config()->local_scopes))) return $this;
        $scopeMethod = function ($name) {
            $mName = method_name('scope_' . $name);
            return (new MethodGenerator($mName))
                ->setDocBlock((new DocBlockGenerator('Local Scope Query for ' . $name . ' ' . in_plural(model_name($this->table->name))))
                    ->setTag(new ParamTag('query', ['Builder']))
                    ->setTag(new ReturnTag(['Builder', 'void'])))
                ->setAbstract(true)
                ->setParameter(new ParameterGenerator('query'));
        };
        $scopes = is_array(LarEloquent::config()->local_scopes[$this->table->name]) ? LarEloquent::config()->local_scopes[$this->table->name] : [LarEloquent::config()->local_scopes[$this->table->name]];
        foreach (array_filter($scopes) as $scope) {
            $this->class->addUse(Builder::class)
                ->addMethodFromGenerator($scopeMethod($scope))
                ->getDocBlock()
                ->setTag((new MethodTag(method_name($scope)))
                    ->setTypes('Builder'));
        }

        return $this;
    }

    private function timestamps(): static
    {
        if (is_a($this, TraitModel::class) || isset($this->createdCol, $this->updatedCol)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('timestamps', false))
                ->setDocBlock((new DocBlockGenerator('Indicates if the model should be timestamped.'))
                    ->setTag((new VarTag('timestamps', 'bool')))));
        return $this;
    }

    private function hidden(): static
    {
        if (is_a($this, TraitModel::class) || !array_key_exists($this->table->name, LarEloquent::config()->hidden_columns)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('hidden',
                array_values(array_intersect(array_map(fn(DBColumn $column) => $column->column_name, $this->columns), LarEloquent::config()->hidden_columns[$this->table->name]))))
                ->setDocBlock((new DocBlockGenerator('The attributes that should be hidden for arrays.'))
                    ->setTag((new VarTag('hidden', 'array'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function guarded(): static
    {
        if (is_a($this, TraitModel::class)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('guarded',
                array_values(array_intersect(array_map(fn(DBColumn $column) => $column->column_name, $this->columns), LarEloquent::config()->guarded_columns[$this->table->name] ?? []))))
                ->setDocBlock((new DocBlockGenerator('The attributes that aren\'t mass assignable.'))
                    ->setTag((new VarTag(types: 'array'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function table_name(): static
    {
        if (is_a($this, TraitModel::class)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('table', (LarEloquent::config()->add_table_schema ? $this->connection->dbname . '.' : '') . $this->table->name))
                ->setDocBlock((new DocBlockGenerator('Table associated with model.'))
                    ->setTag((new VarTag('table', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function primary_key(): static
    {
        if (is_a($this, TraitModel::class) || !isset($this->primaryCol)) return $this;
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
                    ->setFlags([AbstractMemberGenerator::FLAG_PUBLIC]));
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

    private function date_format(): static
    {
        if (is_a($this, TraitModel::class) || !LarEloquent::config()->date_format) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('dateFormat', LarEloquent::config()->date_format))
                ->setDocBlock((new DocBlockGenerator('The storage format of the model\'s date columns.'))
                    ->setTag((new VarTag('dateFormat', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function connection(): static
    {
        if (is_a($this, TraitModel::class) || !LarEloquent::config()->define_connection) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('connection', $this->connection->name))
                ->setDocBlock((new DocBlockGenerator('The database connection that should be used by the model.'))
                    ->setTag((new VarTag('connection', 'string'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function attributes(): static
    {
        if (is_a($this, TraitModel::class) || 0 >= (count($defaults = array_filter($this->columns, function (DBColumn $col) {
                return null !== $col->column_default && null !== $col->defaultValue();
            })))) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('attributes', array_combine(array_map(fn(DBColumn $col) => $col->column_name, $defaults), array_map(fn(DBColumn $col) => $col->defaultValue(), $defaults))))
                ->setDocBlock((new DocBlockGenerator('The model\'s default values for attributes.'))
                    ->setTag((new VarTag('attributes', 'array'))))
                ->setFlags([AbstractMemberGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function typeCasts(): static
    {
        if (is_a($this, TraitModel::class)) return $this;
        $casts = [];
        foreach ($this->columns as $column) {
            $cast = $column->cast();
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