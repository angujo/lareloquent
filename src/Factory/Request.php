<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\DataType;
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
use function Angujo\Lareloquent\flatten_array;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;
use function Angujo\Lareloquent\tag;

class Request extends FileCreator
{
    private DBTable $table;
    /** @var array|DBColumn[] */
    private array  $columns;
    private string $table_namespace;

    private array $rules    = [];
    private array $messages = [];

    public function __construct(DBTable $table, array $columns)
    {
        $this->columns         = $columns;
        $this->table           = $table;
        $this->table_namespace = implode('\\', [LarEloquent::config()->namespace, $this->table_name = model_name($this->table->name)]);
        $this->name            = model_name(LarEloquent::config()->base_request_prefix.'_'.$this->table_name.'_'.LarEloquent::config()->request_suffix);
        $this->namespace       = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix)]);
        $this->parent_class    = FormRequest::class;
        $this->dir             = Path::Combine(LarEloquent::config()->requests_dir, model_name(LarEloquent::config()->base_request_prefix));
        parent::__construct();
        $this->class->setAbstract(true);
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
            ->setAbstract(true)
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Determine if the user is authorized to make this request.')
                              ->setTag(GeneralTag::returnTag('bool')))
            ->setReturnType('bool');
    }

    private function messagesMethod()
    {
        return (new MethodGenerator('messages'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Custom message for validation')
                              ->setTag(GeneralTag::returnTag('array')))
            ->setBody('return '.(new ValueGenerator($this->messages, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate().';');
    }

    private function rulesMethod()
    {
        return (new MethodGenerator('rules'))
            ->setReturnType('array')
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Get the validation rules that apply to the request.')
                              ->setTag(GeneralTag::fromContent('return', 'array')))
            ->setBody('return '.(new ValueGenerator($this->rules, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate().';');
    }

    private function parseRules()
    {
        $this->rules    = array_filter(array_combine(
                                           array_map(function(DBColumn $col){ return $col->column_name; }, $this->columns),
                                           array_map(function(DBColumn $column){
                                               $rules = $this->getRule($column);
                                               if (empty($rules)) return null;
                                               return $rules;
                                           }, $this->columns)));
        $this->messages = $this->getMessages();
    }

    private function getRule(DBColumn $column)
    {
        $rules = [];
        if ($column->increments) return $rules;
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
        if ($column->PhpDataType() === DataType::BOOL) $rules[] = 'boolean';
        if (!empty($column->character_maximum_length) && DataType::STRING === $column->PhpDataType()) $rules[] = "max:{$column->character_maximum_length}";
        if ($column->is_unique) $rules[] = "unique:{$this->table->name},{$column->column_name}";
        if (!empty($column->referenced_column_name)) $rules[] = "exists:{$column->referenced_table_name},{$column->referenced_column_name}";
        return $rules;
    }

    public function getMessages()
    {
        $msgs  = [];
        $rules = array_unique(array_map(function($rl){
            $arr = explode(':', $rl);
            return array_shift($arr);
        }, flatten_array($this->rules)));
        foreach ($rules as $rule) {
            if (array_key_exists($rule, $msgs)) continue;
            $msg = 'Invalid';
            switch ($rule) {
                case 'required':
                    $msg = 'The :attribute field is required and cannot be empty!';
                    break;
                case 'email':
                    $msg = 'Ensure a valid email is entered!';
                    break;
                case 'url':
                    $msg = 'Ensure a valid URL is entered!';
                    break;
                case 'uuid':
                    $msg = 'Invalid UUID has been entered!';
                    break;
                case 'ip':
                    $msg = 'Invalid IP Address was entered. Ensure valid ipv4 or ipv6 is used!';
                    break;
                case 'json':
                    $msg = 'Ensure only valid array inputs are entered';
                    break;
                case 'image':
                    $msg = 'Only images need to be uploaded';
                    break;
                case 'mac_address':
                    $msg = 'Ensure valid mac address is entered!';
                    break;
                case 'integer':
                    $msg = 'Only integers allowed for :attribute field!';
                    break;
                case 'numeric':
                    $msg = 'Only numeric entries allowed for :attribute field!';
                    break;
                case 'max':
                    $msg = 'Only a maximum of :values characters allowed!';
                    break;
                case 'unique':
                    $msg = 'Entered value for :attribute already exist. Only unique values allowed!';
                    break;
                case 'exists':
                    $msg = 'Ensure the referenced entry for :attribute already exist!';
                    break;
            }
            $msgs[$rule] = $msg;
        }
        return $msgs;
    }

    private function compile()
    {
        $this->parseRules();
        $this->class->setDocBlock($this->classDoc())
                    ->addMethodFromGenerator($this->authorizeMethod())
                    ->addMethodFromGenerator($this->rulesMethod())
                    ->addMethodFromGenerator($this->messagesMethod());
        return $this;
    }

    public static function Write(DBTable $table, array $columns)
    : void
    {
        (new self($table, $columns))->compile()->_write();
    }
}