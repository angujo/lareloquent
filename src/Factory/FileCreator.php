<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use function Angujo\Lareloquent\model_file;

abstract class FileCreator
{
    protected string         $name;
    protected string         $parent_class;
    protected string         $namespace;
    protected string         $dir;
    protected ClassGenerator $class;
    protected bool           $overwrites = true;

    public function __construct($overwrites = true)
    {
        $this->overwrites = $overwrites;
        $this->class      = new ClassGenerator($this->name);
        if (!empty($this->namespace)) $this->class->setNamespaceName($this->namespace);
        if (!empty($this->parent_class)) $this->class->setExtendedClass($this->parent_class)->addUse($this->parent_class);
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