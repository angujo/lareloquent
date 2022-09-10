<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class Observer extends FileCreator
{
    private         $events = ['retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted',
                               'trashed', 'forceDeleted', 'restoring', 'restored', 'replicating',];
    private DBTable $table;
    private string  $table_namespace;

    public function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->name  = model_name(($this->table_name = model_name($this->table->name)).'_'.LarEloquent::config()->observer_suffix);
        $this->dir   = LarEloquent::config()->observers_dir;
        parent::__construct(false);
        $this->class->setNamespaceName(LarEloquent::config()->observer_namespace)
                    ->addUse($this->table_namespace = implode('\\', [LarEloquent::config()->namespace, $this->table_name]));
    }

    private function runEvents()
    {
        foreach ($this->events as $event) {
            $this->class->addMethodFromGenerator($this->eventMethod($event));
        }
    }

    private function eventMethod(string $event)
    {
        return (new MethodGenerator($event))
            ->setDocBlock((new DocBlockGenerator("Handle the {$this->table_name} '{$event}' event."))
                              ->setTag(new ParamTag(method_name($this->table->name), [$this->table_name]))
                              ->setTag(new ReturnTag(['void'])))
            ->setParameter(new ParameterGenerator(method_name($this->table->name), $this->table_namespace))
            ->setBody("//TODO Add {$event} event actions here");
    }

    public function __toString()
    : string
    {
        $this->runEvents();
        return parent::__toString();
    }

    public static function Write(DBTable $table)
    : void
    {
        $obs = new self($table);
        $obs->_write();
        ProviderBoot::addObserver($obs->table_namespace, implode('\\', [LarEloquent::config()->observer_namespace, $obs->name]));
    }
}