<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Laminas\Code\Generator\DocBlock\Tag\LicenseTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\flatten_array;
use function Angujo\Lareloquent\model_name;

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
        $this->parent_class    = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix), model_name(LarEloquent::config()->base_request_prefix.'_'.LarEloquent::config()->request_suffix)]);
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
        foreach ($this->columns as $column) {
            $rules = array_merge($this->getRule($column), $column->getValidation());
            if (empty($rules)) continue;
            $this->messages                    = array_merge($this->messages, $this->getMessages(array_keys($rules), $column));
            $this->rules[$column->column_name] = array_map(function($k, $v){ return empty($v) ? $k : "$k:$v"; }, array_keys($rules), $rules);
        }
    }

    private function getRule(DBColumn $column)
    {
        $rules = [];
        if ($column->increments) return $rules;
        if (!$column->is_nullable) $rules['required'] = '';
        if ($column->isEmail()) {
            $rules['email'] = '';
        } elseif ($column->isURL()) $rules['url'] = '';
        elseif ($column->isUUID()) $rules['uuid'] = '';
        elseif ($column->isIpAddress()) $rules['ip'] = '';
        elseif ($column->isJson()) $rules['json'] = '';
        elseif ($column->isArray()) $rules['array'] = '';
        elseif ($column->isImage()) $rules['image'] = '';
        elseif ($column->isFile()) $rules['file'] = '';
        elseif ($column->isMacAddress()) $rules['mac_address'] = '';
        elseif ($column->PhpDataType() === DataType::INT) $rules['integer'] = '';
        elseif ($column->PhpDataType() === DataType::FLOAT) $rules['numeric'] = '';
        elseif ($column->PhpDataType() === DataType::BOOL) $rules['boolean'] = '';
        if (!empty($column->character_maximum_length) && DataType::STRING === $column->PhpDataType()) $rules['max'] = "{$column->character_maximum_length}";
        if ($column->is_unique) $rules['unique'] = "{$this->table->name},{$column->column_name}";
        if (!empty($column->referenced_column_name)) $rules['exists'] = "{$column->referenced_table_name},{$column->referenced_column_name}";
        return $rules;
    }

    public function getMessages($rules, DBColumn $column)
    {
        $msgs = [];
        foreach ($rules as $rule) {
            if (array_key_exists($rule, $msgs) || !array_key_exists($rule, BaseRequest::$default_messages)) continue;
            $msg = null;
            switch ($rule) {
                case 'max':
                    if (DataType::STRING === $column->PhpDataType()) {
                        $msg = 'Only a maximum of :values characters allowed!';
                    } else $msg = 'Only a maximum of value of :values allowed!';
                    break;
                case 'min':
                    if (DataType::STRING === $column->PhpDataType()) {
                        $msg = 'Only a minimum of :values characters allowed!';
                    } else $msg = 'Only a minimum of value of :values allowed!';
                    break;
                case 'size':
                    if (DataType::STRING === $column->PhpDataType()) {
                        $msg = 'A total of :values characters allowed!';
                    } else $msg = 'Only the value :values allowed!';
                    break;
                case 'unique':
                    $msg = 'Entered value for :attribute already exist. Only unique values allowed!';
                    break;
                case 'exists':
                    $msg = 'Ensure the referenced entry for :attribute already exist!';
                    break;
            }
            if (!empty($msg)) {
                $rule = "$column->column_name.$rule";
            } else   $msg = BaseRequest::$default_messages[$rule];
            $msgs[$rule] = $msg;
        }
        return $msgs;
    }

    private function messagesMethod()
    {
        return (new MethodGenerator('messages'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Custom message for validation')
                              ->setTag(GeneralTag::returnTag('array')))
            ->setBody('return '.
                      (new ValueGenerator($this->messages, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate().';');
    }

    private function compile()
    {
        $this->parseRules();
        $this->class->setDocBlock($this->classDoc())
                    ->addMethodFromGenerator($this->rulesMethod());
        if (!empty($this->messages)) $this->class->addMethodFromGenerator($this->messagesMethod());
        return $this;
    }

    public static function Write(DBTable $table, array $columns)
    : void
    {
        (new self($table, $columns))->compile()->_write();
    }
}