<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use function Angujo\Lareloquent\model_name;

class WorkResource extends FileCreator
{
    /** @var DBTable */
    private DBTable $table;

    public function __construct(DBTable $table)
    {
        $this->table        = $table;
        $this->name         = model_name(model_name($this->table_name=$this->table->name).'_'.LarEloquent::config()->resource_suffix);
        $this->parent_class = implode('\\', [LarEloquent::config()->resource_namespace, model_name(LarEloquent::config()->base_resource_prefix), model_name(LarEloquent::config()->base_resource_prefix.'_'.model_name($this->table->name).'_'.LarEloquent::config()->resource_suffix)]);
        $this->dir          = LarEloquent::config()->resources_dir;
        $this->namespace    = LarEloquent::config()->resource_namespace;
        parent::__construct(false);
        $this->class->setDocBlock((new DocBlockGenerator('Work Resource class for the '.model_name($table->name).' model.'))
                                      ->setLongDescription('This file should be used to customize resources for the model!'));
    }

    private function asArrayMethod()
    {
        return (new MethodGenerator('asArray'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Use same way as the toArray method.')
                              ->setLongDescription('Difference is that any column entries will overwrite default values return for the resource. Use it to extend and provide extra conditions as highlighted in the documentation.')
                              ->setTag(GeneralTag::fromContent('see', 'https://laravel.com/docs/eloquent-resources#concept-overview'))
                              ->setTag(GeneralTag::fromContent('see', basename($this->parent_class).'::toArray() for implementation and integration.'))
                              ->setTag(new ParamTag('request', ['Request']))
                              ->setTag(new ReturnTag('array')))
            ->setParameter('request')
            ->setBody('return [];')
            ->setReturnType('array');
    }

    public static function Write(DBTable $table)
    {
        $m = new self($table);
        $m->class->addUse(\Illuminate\Http\Request::class)
                 ->addMethodFromGenerator($m->asArrayMethod());
        $m->_write();
    }
}