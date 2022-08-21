<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Elolara\Model\Relations\BelongsTo;
use Angujo\Lareloquent\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\DocProperty;
use Angujo\Lareloquent\Models\HasTraits;
use Angujo\Lareloquent\Models\HasUsage;
use Angujo\Lareloquent\Models\Polymorphic;
use Angujo\Lareloquent\Path;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function Angujo\Lareloquent\clean_template;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
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
    public $columns = [];
    /** @var DocProperty[] */
    private array $doc_properties = [];
    private array $col_constants  = [];

    protected function __construct(DBTable $table, DBConnection $connection)
    {
        $this->table      = $table;
        $this->name       = model_name(LarEloquent::config()->base_abstract_prefix.'_'.$this->table->name);
        $this->template   = file_get_contents(Path::Template('model.tpl'));
        $this->connection = $connection;
        if (LarEloquent::config()->model_class) LarEloquent::addUsage($table->name, LarEloquent::config()->model_class);
    }

    public function one2One()
    {
        $this->template = str_replace('{one2one}',
                                      implode("\n", array_filter(array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return null;
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->hasOne(".model_name($referential->referenced_table_name)."::class, '{$referential->referenced_column_name}', '{$referential->column_name}');".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->One2One($this->table->name))))),
                                      $this->template);
        return $this;
    }

    public function belongsTo()
    {
        $this->template = str_replace('{belongsto}',
                                      implode("\n", array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return '';
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->belongsTo(".model_name($referential->referenced_table_name)."::class, '{$referential->column_name}', '{$referential->referenced_column_name}');".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->BelongsTo($this->table->name)))),
                                      $this->template);
        return $this;
    }

    public function belongsToMany()
    {
        $this->template = str_replace('{belongstomany}',
                                      implode("\n", array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return null;
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get the ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->belongsToMany(".model_name($referential->referenced_table_name)."::class, '{$referential->through_table_name}', '{$referential->through_column_name}', '{$referential->through_ref_column_name}', '{$referential->column_name}', '{$referential->referenced_column_name}')".
                                              (empty($referential->pivotColumns()) ? '' : (LarEloquent::config()->process_pivot_tables ? '' : '->as(\''.method_name($referential->through_table_name).'\')').'->withPivot('.implode(', ', array_map(function($c){ return "'{$c}'"; }, $referential->pivotColumns())).')').";".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->belongsToMany($this->table->name)))),
                                      $this->template);
        return $this;
    }

    public function one2Many()
    {
        $this->template = str_replace('{hasmany}',
                                      implode("\n", array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return null;
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->hasMany(".model_name($referential->referenced_table_name)."::class, '{$referential->referenced_column_name}', '{$referential->column_name}');".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->One2Many($this->table->name)))),
                                      $this->template);
        return $this;
    }

    public function oneThrough()
    {
        $this->template = str_replace('{onethrough}',
                                      implode("\n", array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return null;
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->hasOneThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '{$referential->through_column_name}', '{$referential->referenced_column_name}', '{$referential->column_name}', '{$referential->through_ref_column_name}');".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->oneThrough($this->table->name)))),
                                      $this->template);
        return $this;
    }

    public function manyThrough()
    : static
    {
        $this->template = str_replace('{manythrough}',
                                      implode("\n", array_map(function(DBReferential $referential){
                                          if (!LarEloquent::validTable($referential->referenced_table_name)) return null;
                                          $this->doc_properties[] = DocProperty::fromReferential($referential, function($n){ return $this->getColumn($n); });
                                          return "\n\t/**".
                                              "\n\t* Get ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name).
                                              "\n\t* @return ".basename($referential->getReturnClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$referential->functionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->hasManyThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '{$referential->through_column_name}', '{$referential->referenced_column_name}', '{$referential->column_name}', '{$referential->through_ref_column_name}');".
                                              "\n\t}";
                                      },
                                          iterator_to_array($this->connection->manyThrough($this->table->name)))),
                                      $this->template);
        return $this;
    }

    public function morphTo()
    {
        $this->template = str_replace('{morphto}',
                                      implode("\n", array_map(function(Polymorphic $polymorphic){
                                          $this->doc_properties[] = DocProperty::fromPolymorphicReferences($polymorphic);
                                          LarEloquent::addUsage($this->table->name, $polymorphic->getMorphToClass());
                                          LarEloquent::addUsage($this->table->name, ...$polymorphic->getReturnableClasses());
                                          ProviderBoot::addMorph($polymorphic->referencedTables());
                                          return "\n\t/**".
                                              "\n\t* Get associated with the ".model_name($polymorphic->table_name).
                                              "\n\t* @return ".basename($polymorphic->getMorphToClass()).
                                              "\n\t*/".
                                              "\n\tpublic function {$polymorphic->actionName()}()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->morphTo('{$polymorphic->morph_name}');".
                                              "\n\t}";
                                      },
                                          Morpher::morphers($this->connection, $this->table->name))),
                                      $this->template);
        return $this;
    }

    public function morphMany()
    {
        $this->template = str_replace('{morphmany}',
                                      implode("\n", array_map(function(Polymorphic $polymorphic){
                                          $this->doc_properties[] = DocProperty::fromPolymorphicMany($polymorphic, $this->table->name);
                                          LarEloquent::addUsage($this->table->name, $polymorphic->getMorphManyClass());
                                          return "\n\t/**".
                                              "\n\t* Get ".model_name(in_plural($polymorphic->table_name))." associated with the ".model_name($this->table->name).
                                              "\n\t* @return ".basename($polymorphic->getMorphManyClass()).
                                              "\n\t*/".
                                              "\n\tpublic function ".method_name(in_plural($polymorphic->table_name))."()".
                                              "\n\t{".
                                              "\n\t\treturn \$this->morphMany(".model_name($polymorphic->table_name)."::class, '{$polymorphic->actionName()}');".
                                              "\n\t}";
                                      },
                                          Morpher::morphs($this->connection, $this->table->name))),
                                      $this->template);
        return $this;
    }

    private function parseColumns()
    {
        $this->columns = iterator_to_array($this->connection->Columns($this->table->name));
        foreach ($this->columns as $column) {
            // $this->addUse($column->GetUses());
            $this->addTrait($column->GetTraits());
            $this->doc_properties[] = DocProperty::fromColumn($column);
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
                                          "\t/**\n\t* Indicates if the model should be timestamped.\n\t*\n\t* @var bool\n\t*/\n\tprotected \$timestamps = false;",
                                          $this->template);
        }
        return $this;
    }

    private function table_name()
    {
        $this->template = str_replace('{table_name}',
                                      "\t/**\n\t* Table associated with model."
                                      ."\n\t*\n\t* @var string\n\t*/\n\tprotected \$table = '{$this->table->name}';",
                                      $this->template);
        return $this;
    }

    private function primary_key()
    {
        if (!isset($this->primaryCol)) return $this;
        $this->template = str_replace('{primary_key}',
                                      "\t/**\n\t* Primary Key associated with model.\n\t*\n\t* @var string\n\t*/\n\tprotected \$primaryKey = '{$this->primaryCol->column_name}';",
                                      $this->template);
        if (!$this->primaryCol->increments) {
            $this->template = str_replace('{primary_key_increment}',
                                          "\t/**\n\t* Indicate if Primary Key is auto-incrementing.\n\t*\n\t* @var bool\n\t*/\n\tprotected \$incrementing = ".var_export($this->primaryCol->increments, true).";",
                                          $this->template);
        }
        if (DataType::INT != $this->primaryCol->PhpDataType()) {
            $this->template = str_replace('{primary_key_type}',
                                          "\t/**\n\t* Data type of Primary Key that's auto-incrementing.\n\t*\n\t* @var string\n\t*/\n\tprotected \$keyType = '{$this->primaryCol->PhpDataType()->value}';",
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
                                      "\t/**\n\t* The storage format of the model's date columns.\n\t*\n\t* @var string\n\t*/\n\tprotected \$dateFormat = '".LarEloquent::config()->date_format."';",
                                      $this->template);
        return $this;
    }

    private function connection()
    {
        if (!LarEloquent::config()->define_connection) return $this;
        $this->template = str_replace('{connection}',
                                      "\t/**\n\t* The database connection that should be used by the model.\n\t*\n\t* @var string\n\t*/\n\tprotected \$connection = '{$this->connection->name}';",
                                      $this->template);
        return $this;
    }

    private function attributes()
    {
        if (0 >= (count($defaults = array_filter($this->columns, function(DBColumn $col){ return null !== $col->column_default && null != $col->defaultValue(); })))) return $this;
        $this->template = str_replace('{attributes}',
                                      "\t/**\n\t* The model's default values for attributes.\n\t*\n\t* @var array\n\t*/\n\tprotected \$attributes = ["
                                      .implode(', ',
                                               array_map(function(DBColumn $column){ return "'{$column->column_name}' => {$column->defaultValue()}"; }, $defaults))."];",
                                      $this->template);
        return $this;
    }

    private function namespace($base = false)
    {
        $this->template = str_replace('{namespace}', LarEloquent::config()->namespace.'\\'.model_name(LarEloquent::config()->base_abstract_prefix), $this->template);
        return $this;
    }

    private function _uses()
    {
        $uses = array_unique(LarEloquent::getUsages($this->table->name));
        usort($uses, function($u_a, $u_b){
            if (($a = strlen($u_a)) == ($b = strlen($u_b))) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        $this->template = str_replace('{uses}', implode("\n", array_map(function(string $use){ return "use {$use};"; }, $uses)), $this->template);
        return $this;
    }

    private function parent()
    {
        $this->template = preg_replace('/\s+(extends)\s+\{parent}/',
                                       LarEloquent::config()->model_class ? " $1 ".LarEloquent::config()->model_class : '',
                                       $this->template);
        return $this;
    }

    private function clean()
    {
        $this->template = clean_template($this->template);
        return $this;
    }

    private function prepare()
    {
        return $this->name()
                    ->namespace()
                    ->parent()
                    ->parseColumns()
                    ->connection()
                    ->table_name()
                    ->attributes()
                    ->primary_key()
                    ->date_format()
                    ->constants()
                    ->timestamps();
    }

    private function columns()
    {
        $this->template = str_replace('{columns}', (count($this->doc_properties) ? ' * ' : '').implode("\n * ", $this->doc_properties), $this->template);
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
        return $this->prepare()
                    ->one2One()
                    ->belongsTo()
                    ->belongsToMany()
                    ->one2Many()
                    ->oneThrough()
                    ->manyThrough()
                    ->morphTo()
                    ->morphMany()
                    ->columns()->_uses()
                    ->traits()->clean();
    }

    public function __toString()
    : string
    {
        $this->_convert();
        return $this->template;
    }

    private function _write(string $path = null)
    : Model
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->base_dir, model_name(LarEloquent::config()->base_abstract_prefix), model_file($this->name));
        $dir  = dirname($path);
        if (!file_exists($dir)) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
        return $this;
    }

    public static function Write(DBConnection $connection, DBTable $table)
    : Model
    {
        return (new Model($table, $connection))->_write();
    }

    public function getColumn(string $name)
    {
        foreach ($this->columns as $column) {
            if (str_equal($column->column_name, $name)) return $column;
        }
        return null;
    }
}