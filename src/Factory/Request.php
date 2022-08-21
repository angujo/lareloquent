<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Illuminate\Foundation\Http\FormRequest;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\LicenseTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\clean_template;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;
use function Angujo\Lareloquent\tag;

class Request
{
    private DBTable $table;
    /** @var array|DBColumn[] */
    private array  $columns;
    private string $table_name;
    private string $table_namespace;
    private string $name;

    private ClassGenerator $class;

    public function __construct(DBTable $table, array $columns)
    {
        $this->class           = new ClassGenerator();
        $this->columns         = $columns;
        $this->table           = $table;
        $this->table_namespace = implode('\\', [LarEloquent::config()->namespace, $this->table_name = model_name($this->table->name)]);
        $this->name            = model_name($this->table_name.'_'.LarEloquent::config()->request_suffix);
        $this->class->setNamespaceName(LarEloquent::config()->request_namespace)
                    ->setName($this->name)
                    ->addUse(FormRequest::class)
                    ->setExtendedClass(FormRequest::class);
    }

    private function classDoc()
    {
        return (new DocBlockGenerator())
            ->setShortDescription('Generated Request file for model '.basename($this->table_namespace))
            ->setLongDescription('This is an auto-generated class and should not be modified external. All changes will be overwritten in next run.')
            ->setTag((new LicenseTag(licenseName: 'MIT')));
    }

    private function authorizeMethod()
    {
        return (new MethodGenerator('authorize'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Determine if the user is authorized to make this request.')
                              ->setTag(GeneralTag::returnTag('bool')))
            ->setReturnType('bool')
            ->setBody('return true;');
    }

    private function messagesMethod()
    {
        return (new MethodGenerator('messages'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Custom message for validation')
                              ->setTag(GeneralTag::returnTag('array')))
            ->setBody('return [];');
    }

    private function rulesMethod()
    {
        return (new MethodGenerator('rules'))
            ->setReturnType('array')
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Get the validation rules that apply to the request.')
                              ->setTag(GeneralTag::fromContent('return', 'array')))
            ->setBody('return '.(new ValueGenerator($this->getRules(), ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('  ')->generate().';');
    }

    private function getRules()
    {
        return array_filter(
            array_combine(
                array_map(function(DBColumn $col){ return $col->column_name; }, $this->columns),
                array_map(function(DBColumn $column){
                    $rules = $this->getRule($column);
                    if (empty($rules)) return null;
                    return $rules;
                }, $this->columns)));
    }

    private function getRule(DBColumn $column)
    {
        $rules = [];
        if (!$column->is_nullable) $rules[] = 'required';
        if (str_contains($column->column_name, 'email')) $rules[] = 'email';
        if (str_contains($column->column_name, 'url')) $rules[] = 'url';
        if (str_contains($column->column_name, 'uuid')) $rules[] = 'uuid';
        if (preg_match('/(^|_)ip(v4|v6)?(_|$)/', $column->column_name)) $rules[] = 'ip';
        if (preg_match('/(^|_)(json|array)(_|$)/', $column->column_name)) $rules[] = 'json';
        if (preg_match('/(^|_)(image|picture)(_|$)/', $column->column_name)) $rules[] = 'image';
        if (preg_match('/(^|_)mac_address(_|$)/', $column->column_name)) $rules[] = 'mac_address';
        if ($column->PhpDataType() === DataType::INT) $rules[] = 'integer';
        if ($column->PhpDataType() === DataType::FLOAT) $rules[] = 'numeric';
        if (!empty($column->character_maximum_length) && DataType::STRING === $column->PhpDataType()) $rules[] = "max:{$column->character_maximum_length}";
        if ($column->is_unique) $rules[] = "unique:{$this->table->name},{$column->column_name}";
        return $rules;
    }

    public function __toString()
    : string
    {
        return (new FileGenerator())
            ->setClass(
                $this->class->setDocBlock($this->classDoc())
                            ->addMethodFromGenerator($this->authorizeMethod())
                            ->addMethodFromGenerator($this->rulesMethod())
                            ->addMethodFromGenerator($this->messagesMethod())
            )->generate();
    }

    private function _write(string $path = null)
    : void
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->requests_dir, model_file($this->name));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
    }

    public static function Write(DBTable $table, array $columns)
    : void
    {
        (new self($table, $columns))->_write();
    }
}