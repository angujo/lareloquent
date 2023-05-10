<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\model_file;

abstract class FileWriter
{
    protected string $name;
    protected string $dir;
    protected bool $overwrites = true;
    protected bool $name_as_is = false;

    abstract function __toString();

    protected function _write(string $path = null, $extension = null): static
    {
        $path = $path ?? Path::Combine($this->dir, model_file($this->name, $extension, $this->name_as_is));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        if (file_exists($path) && !$this->overwrites) return $this;
        file_put_contents($path, $this . '');
        return $this;
    }
}
