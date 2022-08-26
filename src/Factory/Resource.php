<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use Illuminate\Http\Resources\Json\JsonResource;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\model_name;

class Resource extends FileCreator
{
    /** @var DBTable */
    private DBTable $table;
    /** @var array|DBColumn[] */
    private array $columns = [];

    public function __construct(DBTable $table, array $columns)
    {
        $this->columns      = $columns;
        $this->table        = $table;
        $this->name         = model_name(LarEloquent::config()->base_resource_prefix.'_'.$this->table->name.'_'.LarEloquent::config()->resource_suffix);
        $this->parent_class = JsonResource::class;
        $this->dir          = Path::Combine(LarEloquent::config()->resources_dir, model_name(LarEloquent::config()->base_resource_prefix));
        $this->namespace    = implode('\\', [LarEloquent::config()->resource_namespace, model_name(LarEloquent::config()->base_resource_prefix)]);
        parent::__construct();
        $this->class->setAbstract(true)
                    ->setDocBlock((new DocBlockGenerator('Base Resource class for the '.model_name($table->name).' model.'))
                                      ->setLongDescription('This file should not be modified as it is to be overwritten when regenerated!'));
    }

    private function asArrayMethod()
    {
        return (new MethodGenerator('asArray'))
            ->setAbstract(true)
            ->setParameter(new ParameterGenerator('request'))
            ->setDocBlock((new DocBlockGenerator('Method to extend default resource variables.', 'Return array with extra or overwriting keys to be merged with default model resource variables.'))
                              ->setTag(new ParamTag('request', ['Request']))
                              ->setTag(new ReturnTag('array')))
            ->setReturnType('array');
    }

    private function toArrayMethod()
    {
        $body   = ['$defaults = '.
                   (new ValueGenerator(array_combine(
                                           array_map(fn(DBColumn $column) => $column->column_name, $this->columns),
                                           array_map(fn(DBColumn $column) => (new ValueGenerator("\$this->{$column->column_name}"))
                                               ->setType(ValueGenerator::TYPE_CONSTANT), $this->columns)))).';'];
        $body[] = 'return array_merge($defaults, $this->asArray($request));';
        return (new MethodGenerator('toArray'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Transform the resource into an array.')
                              ->setTag(new ParamTag('request', ['Request']))
                              ->setTag(new ReturnTag('array')))
            ->setParameter('request')
            ->setBody(implode("\n", $body))
            ->setReturnType('array');
    }

    public static function Write(DBTable $table, array $columns)
    {
        $m = new self($table, $columns);
        $m->class->addUse(\Illuminate\Http\Request::class)
                 ->addMethodFromGenerator($m->asArrayMethod())
                 ->addMethodFromGenerator($m->toArrayMethod());
        $m->_write();
    }
}