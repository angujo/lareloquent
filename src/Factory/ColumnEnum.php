<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\EnumGenerator\EnumGenerator;
use Laminas\Code\Generator\FileGenerator;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class ColumnEnum
{
    private               $values = [];
    private EnumGenerator $enum;
    private string        $name;

    private function __construct(DBColumn $column)
    {
        $types        = preg_replace(['/^enum\((.*?)\)$/', '/\'/'], ['[$1]', '"'], $column->column_type);
        $this->values = json_decode($types, false);
        if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('Invalid enum content!');
        $this->enum = EnumGenerator::withConfig(
            ['name'        => $this->name = $column->enumClass(),
             'backedCases' => ['type' => 'string', 'cases' => array_combine(array_map('Angujo\Lareloquent\enum_case', $this->values), $this->values)]]);
    }

    public function __toString()
    : string
    {
        return (new FileGenerator())->setBody($this->enum->generate())->generate();
    }

    protected function _write()
    : static
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->enums_dir, model_file(basename($this->name)));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
        return $this;
    }

    public static function Write(DBColumn $column)
    {
        (new self($column))->_write();
    }
}
