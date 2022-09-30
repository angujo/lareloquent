<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\TraitGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

abstract class FileCreator
{
    protected string  $name;
    protected ?string $table_name;
    protected ?string $parent_class;
    protected string  $namespace;
    protected string  $dir;
    /** @var ClassGenerator|TraitGenerator */
    protected TraitGenerator|ClassGenerator $class;
    protected bool                          $overwrites = true;

    public function __construct(bool $overwrites = true)
    {
        $this->overwrites = $overwrites ?: (defined('LARELOQ_TEST') && LARELOQ_TEST);
        $this->class      = new ClassGenerator($this->name);
        if (!empty($this->namespace)) $this->class->setNamespaceName($this->namespace);
        if (!empty($this->parent_class) && !is_a($this, TraitModel::class)) {
            $alias = null;
            if (!empty($this->table_name) && str_equal(basename($this->parent_class), model_name($this->table_name))) {
                $alias = model_name(implode('_', array_slice(explode('\\', $this->parent_class), -2, 2)));
            }
            $this->class->addUse($this->parent_class, $alias)->setExtendedClass($this->parent_class);
        }
    }

    public function __toString()
    : string
    {
        return (new FileGenerator())->setClass($this->class)->generate();
    }


    protected function _write(string $path = null)
    : static
    {
        $path = $path ?? Path::Combine($this->dir, model_file($this->name));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        if (file_exists($path) && !$this->overwrites) return $this;
        file_put_contents($path, $this.'');
        return $this;
    }
}