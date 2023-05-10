<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\RecursiveMethod;
use Angujo\Lareloquent\Enums\TSType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBEnum;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\clean_placeholders;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class TypeScriptClass extends FileWriter
{
    private string $template;
    private bool $asInterface = false;
    private EloqModel $model;
    /**
     * @var string[]
     */
    private array $imports = [];

    private function __construct(EloqModel $model)
    {
        $this->asInterface = $this->name_as_is = LarEloquent::config()->typescript_interface;
        $this->model = $model;
        $this->name = ($this->asInterface ? 'I' : '') . model_name($this->model->table->name);
        $this->dir = LarEloquent::config()->typescript_dir;
        $this->template = file_get_contents(Path::Combine(Path::$BASE, 'template', 'class.ts.tpl')) ?: '';
    }

    public function setName(): static
    {
        $this->template = preg_replace('/\{name\}/', $this->name, $this->template);
        return $this;
    }

    public function setType(): static
    {
        $this->template = preg_replace('/\{type\}/', ($this->asInterface ? 'interface' : 'class'), $this->template);
        return $this;
    }

    public function setColumns(): static
    {
        $this->template = preg_replace('/\{properties\}/',
            implode("\n\t", array_map(function (DBColumn $column) {
                if ($column->isEnum()) $this->imports[] = $column->getEnum()->getName();
                return $column->tsPropertyName() . ': ' . $column->tsTypeValue() .
                    (null == $column->tsValue() || $this->asInterface ? '' : ' = ' . $column->tsValue()) . ';';
            }, $this->model->columns)),
            $this->template);
        return $this;
    }

    public function setRelations(): static
    {
        $this->template = preg_replace('/\{methods\}/',
            implode("\n\t", array_filter(
                array_map(function (DBReferential $ref) {
                    if ($ref->is_ignored) return null;
                    if (!str_equal($this->name, $ref->tsReference($this->asInterface))) $this->imports[] = $ref->tsReference($this->asInterface);
                    return $ref->tsPropertyName() . ': ' . $ref->tsTypeValue($this->asInterface) . ';';
                }, $this->model->referentials))),
            $this->template);
        return $this;
    }

    public function cleanTemplate($code = '(.*?)'): static
    {
        $this->template = clean_placeholders($this->template, $code);
        return $this;
    }

    private function setImports(): static
    {
        $this->template = (empty($this->imports) ? '' : (implode("\n", array_map(function (string $name) {
                    return 'import {' . $name . '} from "./' . $name . '"';
                }, array_unique($this->imports)))
                . "\n\n")) . $this->template;
        return $this;
    }

    private function setRecursives()
    {
        if (!$this->model->has_recursives) return $this->cleanTemplate('recursives');
        $this->template = preg_replace('/\{recursives\}/',
            implode("\n\t", array_map(function (RecursiveMethod $method) {
                return $method->tsPropertyName() . ': ' . $method->tsTypeValue($this->name) . ';';
            }, RecursiveMethod::cases())),
            $this->template);
        return $this;
    }

    private function compile(): TypeScriptClass
    {
        return $this->setName()
            ->setType()
            ->setColumns()
            ->setRelations()
            ->setRecursives()
            ->cleanTemplate()
            ->setImports();
    }

    public function __toString()
    {
        return $this->compile()->template;
    }

    public static function Write(EloqModel $model)
    {
        (new self($model))->_write(extension: 'ts');
    }
}