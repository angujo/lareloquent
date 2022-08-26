<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\DocProperty;
use Angujo\Lareloquent\Models\HasUsage;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use function Angujo\Lareloquent\clean_template;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class WorkModel extends FileCreator
{
    use HasUsage;

    private DBTable $table;

    protected function __construct(DBTable $table)
    {
        $this->table        = $table;
        $this->parent_class = implode('\\', [LarEloquent::config()->namespace, model_name(LarEloquent::config()->base_abstract_prefix), model_name(LarEloquent::config()->base_abstract_prefix.'_'.$table->name)]);
        $this->name         = model_name($this->table->name);
        $this->dir          =LarEloquent::config()->base_dir;

        parent::__construct(false);
        $this->class->setNamespaceName(LarEloquent::config()->namespace)
                    ->setDocBlock((new DocBlockGenerator())
                                      ->setShortDescription('Working class to be used for custom extensions')
                                      ->setLongDescription('Use to add your custom code and to overwrite default methods.'));
    }

    public static function Write(DBTable $table)
    : void
    {
        (new WorkModel($table))->_write();
    }
}
