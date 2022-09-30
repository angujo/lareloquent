<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\model_name;

class ModelFactory extends FileCreator
{

    /** @var DBColumn[] */
    private array   $columns;
    private DBTable $table;
    private string  $table_namespace;

    public function __construct(DBTable $table, array $columns)
    {
        $this->columns         = $columns;
        $this->table           = $table;
        $this->table_namespace = implode('\\', [LarEloquent::config()->namespace, model_name($this->table_name = $this->table->name)]);
        $this->name            = model_name($this->table_name).'Factory';
        $this->namespace       = LarEloquent::config()->factories_namespace;
        $this->parent_class    = Factory::class;
        $this->dir             = LarEloquent::config()->factories_dir;
        parent::__construct(false);
    }

    private function modelProperty()
    {
        return (new PropertyGenerator('model'))
            ->setDefaultValue($this->table_namespace)
            ->setDocBlock((new DocBlockGenerator('The name of the factory\'s corresponding model'))->setTag(new VarTag(types: 'string')));
    }

    private function definitionMethod()
    {
        $columns = array_column(array_filter($this->columns, fn(DBColumn $col) => !$col->increments && !($col->isCreatedColumn() || $col->isDeletedColumn() || $col->isUpdatedColumn())), 'column_name');
        return (new MethodGenerator('definition'))
            ->setBody('return '.(new ValueGenerator(array_combine($columns, array_map(fn() => null, $columns))))->generate().';')
            ->setDocBlock((new DocBlockGenerator('Define the model\'s default state.'))->setTag(new ReturnTag('array')));
    }

    private function compile()
    {
        $this->class->addPropertyFromGenerator($this->modelProperty())
                    ->addMethodFromGenerator($this->definitionMethod());
        return $this;
    }

    public static function Write(DBTable $table, array $columns)
    : void
    {
        (new self($table, $columns))->compile()->_write();
    }
}