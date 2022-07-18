<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\HasTraits;
use Angujo\Lareloquent\Models\HasUsage;
use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class Model
{
    use HasUsage, HasTraits;

    private DBTable      $table;
    private string       $name;
    private string       $template;
    private DBConnection $connection;
    private DBColumn     $primaryCol;
    private DBColumn     $createdCol;
    private DBColumn     $updatedCol;
    private DBColumn     $deletedCol;

    /** @var array|DBColumn[] */
    private       $columns        = [];
    private array $col_properties = [];
    private array $col_constants  = [];

    protected function __construct(DBTable $table, DBConnection $connection)
    {
        $this->table      = $table;
        $this->name       = model_name($this->table->name);
        $this->template   = file_get_contents(Path::Combine(Path::$BASE, 'templates', 'model.tpl'));
        $this->connection = $connection;
    }

    private function parseColumns()
    {
        $this->columns = iterator_to_array($this->connection->Columns($this->table->name));
        foreach ($this->columns as $column) {
            $this->addUse($column->GetUses());
            $this->addTrait($column->GetTraits());
            if (!isset($this->primaryCol) && $column->is_primary) $this->primaryCol = $column;
            if (LarEloquent::config()->constant_column_names) $this->col_constants[$column->column_name] = $column->column_name;

            if (!isset($this->createdCol) && $column->isCreatedColumn()) {
                if (!str_equal('created_at', ($this->createdCol = $column)->column_name)) $this->col_constants['created_at'] = $this->createdCol->column_name;
            }
            if (!isset($this->updatedCol) && $column->isUpdatedColumn()) {
                if (!str_equal('updated_at', ($this->updatedCol = $column)->column_name)) $this->col_constants['updated_at'] = $this->updatedCol->column_name;
            }
            if (!isset($this->deletedCol) && $column->isDeletedColumn()) {
                if (!str_equal('deleted_at', ($this->deletedCol = $column)->column_name)) $this->col_constants['deleted_at'] = $this->deletedCol->column_name;
            }
        }
        return $this;
    }

    private function timestamps()
    {
        if (!isset($this->createdCol, $this->updatedCol)) {
            $this->template = str_replace('{timestamps}',
                                          "\t/**\n\t* Indicates if the model should be timestamped.\n\t*\n\t* @var bool\n\t*/\n\tprotected bool \$timestamps = false;",
                                          $this->template);
        }
        return $this;
    }

    private function table_name()
    {
        $this->template = str_replace('{table_name}',
                                      "\t/**\n\t* Table associated with model."
                                      ."\n\t*\n\t* @var string\n\t*/\n\tprotected string \$table = '{$this->table->name}';",
                                      $this->template);
        return $this;
    }

    private function primary_key()
    {
        if (!isset($this->primaryCol)) return $this;
        $this->template = str_replace('{primary_key}',
                                      "\t/**\n\t* Primary Key associated with model.\n\t*\n\t* @var string\n\t*/\n\tprotected string \$primaryKey = '{$this->primaryCol->column_name}';",
                                      $this->template);
        if (!$this->primaryCol->increments) {
            $this->template = str_replace('{primary_key_increment}',
                                          "\t/**\n\t* Indicate if Primary Key is auto-incrementing.\n\t*\n\t* @var bool\n\t*/\n\tprotected bool \$incrementing = ".var_export($this->primaryCol->increments, true).";",
                                          $this->template);
        }
        if (!str_equal('int', $this->primaryCol->PhpDataType())) {
            $this->template = str_replace('{primary_key_type}',
                                          "\t/**\n\t* Data type of Primary Key that's auto-incrementing.\n\t*\n\t* @var string\n\t*/\n\tprotected string \$keyType = '{$this->primaryCol->PhpDataType()}';",
                                          $this->template);
        }
        return $this;
    }

    private function name()
    {
        $this->template = str_replace('{name}', $this->name, $this->template);
        return $this;
    }

    private function date_format()
    {
        if (!LarEloquent::config()->date_format) return $this;
        $this->template = str_replace('{date_format}',
                                      "\t/**\n\t* The storage format of the model's date columns.\n\t*\n\t* @var string\n\t*/\n\tprotected string \$dateFormat = '".LarEloquent::config()->date_format."';",
                                      $this->template);
        return $this;
    }

    private function connection()
    {
        if (!LarEloquent::config()->define_connection) return $this;
        $this->template = str_replace('{connection}',
                                      "\t/**\n\t* The database connection that should be used by the model.\n\t*\n\t* @var string\n\t*/\n\tprotected string \$connection = '{$this->connection->name}';",
                                      $this->template);
        return $this;
    }

    private function attributes()
    {
        $this->template = str_replace('{attributes}',
                                      "\t/**\n\t* The model's default values for attributes.\n\t*\n\t* @var array\n\t*/\n\tprotected array \$attributes = ["
                                      .implode(', ', array_map(function(DBColumn $column){ return "'{$column->column_name}' => null"; }, $this->columns))."];",
                                      $this->template);
        return $this;
    }

    private function namespace()
    {
        $this->template = str_replace('{namespace}', LarEloquent::config()->namespace, $this->template);
        return $this;
    }

    private function _uses()
    {
        $this->template = str_replace('{uses}', implode("\n", array_map(function(string $use){ return "use {$use};"; }, array_unique($this->uses))), $this->template);
        return $this;
    }

    private function parent()
    {
        $this->template = preg_replace('/\s+(extends)\s+\{parent}/', LarEloquent::config()->model_class ? " $1 ".LarEloquent::config()->model_class : '', $this->template);
        return $this;
    }

    private function clean()
    {
        $this->template = preg_replace('/([\n\s\r]+)?\{(.*?)\}/', '', $this->template);
        return $this;
    }

    private function prepare()
    {
        return $this->parseColumns()
                    ->name()
                    ->namespace()
                    ->connection()
                    ->table_name()
                    ->attributes()
                    ->parent()
                    ->_uses()
                    ->primary_key()
                    ->date_format()
                    ->constants()
                    ->traits()
                    ->timestamps();
    }

    private function columns()
    {
        $this->template = str_replace('{columns}',
                                      implode("\n", array_map(function(DBColumn $column){ return "* @property {$column->dataType()} \${$column->column_name}"; }, $this->columns)),
                                      $this->template);
        return $this;
    }

    private function constants()
    {
        $this->template = str_replace('{constants}',
                                      implode("\n", array_map(function($val, $key){ return "\tconst ".strtoupper($key).' = '.var_export($val, true).';'; }, $this->col_constants, array_keys($this->col_constants)))."\n",
                                      $this->template);
        return $this;
    }

    private function traits()
    {
        $exist          = count($this->traits) > 0;
        $this->template = str_replace('{traits}',
                                      ($exist ? "\tuse " : '').implode(", ", array_map(function($val){ return $val; }, $this->traits)).($exist ? ";\n" : ''),
                                      $this->template);
        return $this;
    }

    private function _convert()
    {
        $this->prepare()
             ->columns()
             ->clean();
        return $this;
    }

    public function __toString()
    : string
    {
        $this->_convert();
        return $this->template;
    }

    private function _write(string $path = null)
    : void
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->base_dir, model_file($this->table->name));
        file_put_contents($path, $this.'');
    }

    public static function Write(DBConnection $connection, DBTable $table)
    : void
    {
        (new Model($table, $connection))->_write();
    }
}