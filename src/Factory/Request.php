<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Illuminate\Validation\Rule;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\DocBlock\Tag\LicenseTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\array_search_fn;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class Request extends FileCreator
{
    private DBTable $table;
    /** @var array|DBColumn[] */
    private array $columns;
    private string $table_namespace;

    private array $general_rules = [];
    private array $non_update_rules = [];
    private array $messages = [];
    private ?DBColumn $primaryColumn = null;

    public function __construct(DBTable $table, array $columns)
    {
        $this->columns = $columns;
        $this->table = $table;
        $this->table_namespace = implode('\\', [LarEloquent::config()->namespace, $this->table_name = model_name($this->table->name)]);
        $this->name = model_name(LarEloquent::config()->base_request_prefix . '_' . $this->table_name . '_' . LarEloquent::config()->request_suffix);
        $this->namespace = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix)]);
        $this->parent_class = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix), model_name(LarEloquent::config()->base_request_prefix . '_' . LarEloquent::config()->request_suffix)]);
        $this->dir = Path::Combine(LarEloquent::config()->requests_dir, model_name(LarEloquent::config()->base_request_prefix));
        parent::__construct();
        $this->class->setAbstract(true);
    }

    private function classDoc()
    {
        return (new DocBlockGenerator())
            ->setShortDescription('Generated Request file for model ' . basename($this->table_namespace))
            ->setLongDescription('This is an auto-generated class and should not be modified external. All changes will be overwritten in next run.')
            ->setTag((new LicenseTag(licenseName: 'MIT')));
    }

    private function isLoadedMethod(): MethodGenerator
    {
        $class = implode('\\', [LarEloquent::config()->namespace, model_name($this->table->name)]);
        $this->class->addPropertyFromGenerator((new PropertyGenerator('is_loaded', null, AbstractMemberGenerator::FLAG_PRIVATE)))
            ->addUse($class)
            ->addUse(Rule::class);
        return (new MethodGenerator('isLoaded'))
            ->setReturnType('bool')
            ->setDocBlock((new DocBlockGenerator())
                ->setShortDescription('Method to check if the model is being uploaded.')
                ->setTag(GeneralTag::fromContent('return', 'bool')))
            ->setBody("if (null!==\$this->is_loaded) return \$this->is_loaded;\n"
                . "foreach (\$this->route()->parameters as \$parameter) {\n"
                . "\tif (is_a(\$parameter, " . basename($class) . "::class)) return \$this->is_loaded = true;\n"
                . "}\n"
                . "return \$this->is_loaded = " .
                ($this->primaryColumn ?
                    "\$this->has('" . $this->primaryColumn->column_name . "') && !empty(\$this->get('" . $this->primaryColumn->column_name . "'))" :
                    "true") . ';')
            ->setFlags(AbstractMemberGenerator::FLAG_PROTECTED);

    }

    private function rulesMethod()
    {
        $body = [];
        if (!LarEloquent::config()->update_method || !is_string(LarEloquent::config()->update_method) || str_equal('post', LarEloquent::config()->update_method)) {
            $this->general_rules = array_merge_recursive($this->non_update_rules, $this->general_rules);
            $this->non_update_rules = [];
        }
        $body[] = (empty($this->non_update_rules) ? 'return ' : "\$rules = ") . (new ValueGenerator($this->general_rules, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate() . ';';
        if (!empty($this->non_update_rules)) {
            $method = strtoupper(LarEloquent::config()->update_method);
            $body[] = "if (!\$this->isMethod('{$method}')) {";
            $body[] = "\t\$update_rules = " . (new ValueGenerator($this->non_update_rules, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('        ')->generate() . ";";
            $body[] = "\t\$rules = array_merge_recursive(\$update_rules, \$rules);";
            $body[] = '}';
        }
        $body[] = 'return $rules;';
        return (new MethodGenerator('rules'))
            ->setReturnType('array')
            ->setDocBlock((new DocBlockGenerator())
                ->setShortDescription('Get the validation rules that apply to the request.')
                ->setTag(GeneralTag::fromContent('return', 'array')))
            ->setBody(implode("\n", $body));
    }

    private function parseRules()
    {
        foreach ($this->columns as $column) {
            if (empty($this->primaryColumn) && ($column->is_primary || ($this->table->is_view && str_equal(LarEloquent::config()->primary_key_name, $column->column_name)))) $this->primaryColumn = $column;
            $rules = array_merge($this->getRule($column), $column->getValidation());
            if (empty($rules)) continue;
            $this->messages = array_merge($this->messages, $this->getMessages(array_keys($rules), $column));
            foreach ($rules as $rule => $val) {
                if (str_equal('required', $rule)) {
                    $this->non_update_rules[$column->column_name] = ['required'];//= (new ValueGen("Rule::requiredIf(function(){ return !\$this->isLoaded(); })", ValueGen::TYPE_ASIS));
                } elseif (str_equal('unique', $rule)) {
                    unset($this->general_rules[$column->column_name]['unique']);
                    $this->general_rules[$column->column_name][] = (new ValueGen("Rule::unique('{$this->table->name}','{$column->column_name}')->ignore(intval(\$this->input('{$this->primaryColumn->column_name}',0)))", ValueGen::TYPE_ASIS));
                    $this->class->addUse(Rule::class);
                } else $this->general_rules[$column->column_name][] = empty($val) ? $rule : "$rule:$val";
            }
            if (($prule = $this->checkPeriodDate($column)))  $this->general_rules[$column->column_name][] = $prule;
        }
    }

    private function checkPeriodDate(DBColumn $column)
    {
        if (!$column->isDateTime()) return null;
        if (false === ($key = array_search_fn(LarEloquent::config()->period_date_prefixes, fn($v) => str_starts_with($column->column_name, $v)))) return null;

        $prefix = LarEloquent::config()->period_date_prefixes[$key];
        $other_col = LarEloquent::config()->period_date_prefixes[0 === $key ? 1 : 0] . preg_replace("/^{$prefix}/", '', $column->column_name);
        return ($skey = array_search_fn($this->columns, fn(DBColumn $scol) => str_equal($other_col, $scol->column_name))) ? (($key ? 'after_or_equal' : 'before_or_equal') . ':' . $this->columns[$skey]->column_name) : null;
    }

    private function getRule(DBColumn $column)
    {
        $rules = [];
        if ($column->increments) return $rules;
        if (!$column->is_nullable) $rules['required'] = '';
        else $rules['nullable'] = '';
        if ($column->isEmail()) {
            $rules['email'] = '';
        } elseif ($column->isURL()) $rules['url'] = '';
        elseif ($column->isDateTime()) $rules['date'] = '';
        elseif ($column->isUUID()) $rules['uuid'] = '';
        elseif ($column->isIpAddress()) $rules['ip'] = '';
        elseif ($column->isArray()) $rules['array'] = '';
        elseif ($column->isJson()) $rules['json'] = '';
        elseif ($column->isImage()) $rules['image'] = '';
        elseif ($column->isFile()) $rules['file'] = '';
        elseif ($column->isMacAddress()) $rules['mac_address'] = '';

        if ($column->PhpDataType() === DataType::INT) {
            $rules['integer'] = '';
        } elseif ($column->PhpDataType() === DataType::FLOAT) $rules['numeric'] = '';
        elseif ($column->PhpDataType() === DataType::BOOL) $rules['boolean'] = '';

        if (!empty($column->character_maximum_length) && DataType::STRING === $column->PhpDataType()) $rules['max'] = "{$column->character_maximum_length}";
        if ($column->is_unique) $rules['unique'] = '';// "{$this->table->name},{$column->column_name}";
        if (!empty($column->referenced_column_name)) $rules['exists'] = "{$column->referenced_table_name},{$column->referenced_column_name}";
        return $rules;
    }

    public function getMessages($rules, DBColumn $column)
    {
        $messages = [];
        foreach ($rules as $rule) {
            if (array_key_exists($rule, $messages) || !array_key_exists($rule, BaseRequest::$default_messages)) continue;
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
            $messages[$rule] = $msg;
        }
        return $messages;
    }

    private function messagesMethod()
    {
        return (new MethodGenerator('messages'))
            ->setDocBlock((new DocBlockGenerator())
                ->setShortDescription('Custom message for validation')
                ->setTag(GeneralTag::returnTag('array')))
            ->setBody('return ' .
                (new ValueGenerator($this->messages, ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate() . ';');
    }

    private function compile()
    {
        $this->parseRules();
        $this->class->setDocBlock($this->classDoc())
            // ->addMethodFromGenerator($this->isLoadedMethod())
            ->addMethodFromGenerator($this->rulesMethod());
        if (!empty($this->messages)) $this->class->addMethodFromGenerator($this->messagesMethod());
        return $this;
    }

    public static function Write(DBTable $table, array $columns): void
    {
        (new self($table, $columns))->compile()->_write();
    }
}