<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\HasUsage;
use Laminas\Code\Generator\DocBlockGenerator;
use function Angujo\Lareloquent\model_name;

class WorkModel extends FileCreator
{
    use HasUsage;

    private DBTable $table;

    protected function __construct(DBTable $table)
    {
        $trait              = LarEloquent::config()->model_trait && in_array($table->name, LarEloquent::config()->trait_model_tables);
        $this->table        = $table;
        $this->table_name   = $table->name;
        $this->parent_class = implode('\\', [LarEloquent::config()->namespace, model_name(LarEloquent::config()->base_abstract_prefix), model_name(LarEloquent::config()->base_abstract_prefix.'_'.$table->name)]);
        $trait_class        = null;
        if ($trait) {
            $trait_class = $this->parent_class;
            if (array_key_exists($this->table_name, LarEloquent::config()->custom_extends)) $this->parent_class = LarEloquent::config()->custom_extends[$table->name];
        }


        $this->name = model_name($this->table->name);
        $this->dir  = LarEloquent::config()->base_dir;

        parent::__construct(false);
        $this->class->setNamespaceName(LarEloquent::config()->namespace)
                    ->setDocBlock((new DocBlockGenerator())
                                      ->setShortDescription('Working class to be used for custom extensions')
                                      ->setLongDescription('Use to add your custom code and to overwrite default methods.'));
        if ($trait_class) $this->class->addUse($trait_class)->addTrait(basename($trait_class));
    }

    public static function Write(DBTable $table)
    : void
    {
        (new WorkModel($table))->_write();
    }
}
