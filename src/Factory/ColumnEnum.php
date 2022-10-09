<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBEnum;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\EnumGenerator\EnumGenerator;
use Laminas\Code\Generator\FileGenerator;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\str_equal;

class ColumnEnum
{
    private EnumGenerator $enum;
    private DBEnum        $dbEnum;

    private function __construct(DBEnum $DBEnum)
    {
        $this->dbEnum = $DBEnum;
        $this->process();
    }

    public function process()
    {
        $cases = $this->dbEnum->cases();
        if (enum_exists($this->dbEnum->className())) {
            $refl = new \ReflectionEnum($this->dbEnum->className());
            foreach ($refl->getCases() as $case) {
                if (array_key_exists($case->getName(), $cases) && str_equal($case->getValue()->value, $cases[$case->getName()])) continue;
                $cases[$case->getName()] = $case->getValue()->value;
            }
        }
        $this->enum = EnumGenerator::withConfig(
            ['name'        => $this->dbEnum->className(),
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
        $path = $path ?? Path::Combine(LarEloquent::config()->enums_dir, model_file(basename($this->dbEnum->className())));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
        return $this;
    }

    public static function Write(DBEnum $enum)
    {
        (new self($enum))->_write();
    }
}
