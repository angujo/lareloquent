<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Illuminate\Http\Resources\Json\JsonResource;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\model_name;

class WorkRequest extends FileCreator
{
    /** @var DBTable */
    private DBTable $table;

    public function __construct(DBTable $table)
    {
        $this->table        = $table;
        $this->name         = model_name(model_name($this->table_name=$this->table->name).'_'.LarEloquent::config()->request_suffix);
        $this->parent_class = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix), model_name(LarEloquent::config()->base_request_prefix.'_'.model_name($this->table->name).'_'.LarEloquent::config()->request_suffix)]);
        $this->dir          = LarEloquent::config()->requests_dir;
        $this->namespace    = LarEloquent::config()->request_namespace;
        parent::__construct(false);
        $this->class->setDocBlock((new DocBlockGenerator('Work Request class for the '.model_name($table->name).' model.'))
                                      ->setLongDescription('This file should be used to customize requests for the model!'));
    }

    private function authorizeMethod()
    {
        return (new MethodGenerator('authorize'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Determine if the user is authorized to make this request.')
                              ->setTag(GeneralTag::returnTag('bool')))
            ->setReturnType('bool')
            ->setBody('return true;');
    }

    public static function Write(DBTable $table)
    {
        $m = new self($table);
        $m->class->addMethodFromGenerator($m->authorizeMethod());
        $m->_write();
    }
}