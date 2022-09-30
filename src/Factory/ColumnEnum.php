<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\EnumGenerator\EnumGenerator;
use Laminas\Code\Generator\FileGenerator;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\str_equal;

class ColumnEnum
{
    private               $values = [];
    private EnumGenerator $enum;
    private string        $name;
    private DBColumn      $column;

    private function __construct(DBColumn $column)
    {
        $this->column = $column;
        $types        = preg_replace(['/^enum\((.*?)\)$/', '/\'/'], ['[$1]', '"'], $this->column->column_type);
        $this->values = json_decode($types, false);
        if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('Invalid enum content!');
        $this->name = $this->column->enumClass();
        $this->process($this->name);
    }

    public function process(string $target_class = null)
    {
        $cases = array_combine(array_map('Angujo\Lareloquent\enum_case', $this->values), $this->values);
        if (!empty($target_class) && enum_exists($target_class)) {
            $refl = new \ReflectionEnum($target_class);
            foreach ($refl->getCases() as $case) {
                if (array_key_exists($case->getName(), $cases) && str_equal($case->getValue()->value, $cases[$case->getName()])) continue;
                $cases[$case->getName()] = $case->getValue()->value;
            }
        }
        $this->enum = EnumGenerator::withConfig(
            ['name'        => $this->name,
             'backedCases' => ['type' => 'string', 'cases' => $cases]]);
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
