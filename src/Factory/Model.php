<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\DocProperty;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Models\HasTraits;
use Angujo\Lareloquent\Models\HasUsage;
use Angujo\Lareloquent\Models\Polymorphic;
use Angujo\Lareloquent\Path;
use Angujo\Lareloquent\Enums\SQLType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;
use function Angujo\Lareloquent\str_rand;

class Model extends FileCreator
{
    use HasUsage, HasTraits;

    private DBTable      $table;
    private DBConnection $connection;
    private DBColumn     $primaryCol;
    private DBColumn     $createdCol;
    private DBColumn     $updatedCol;
    private DBColumn     $deletedCol;

    /** @var array|DBColumn[] */
    public array $columns = [];

    protected function __construct(DBTable $table, DBConnection $connection)
    {
        $this->table        = $table;
        $this->name         = model_name(LarEloquent::config()->base_abstract_prefix.'_'.$this->table->name);
        $this->connection   = $connection;
        $this->parent_class = LarEloquent::config()->model_class;
        $this->namespace    = LarEloquent::config()->namespace.'\\'.model_name(LarEloquent::config()->base_abstract_prefix);
        $this->dir          = Path::Combine(LarEloquent::config()->base_dir, model_name(LarEloquent::config()->base_abstract_prefix));
        parent::__construct();
        $this->class->setAbstract(true)
                    ->setDocBlock((new DocBlockGenerator('The Table mapping for model.'))
                                      ->setLongDescription('This is the base model for direct mapping of the DB table.'));
    }

    private function referentialMethod(DBReferential $referential, string $body, string $description = '')
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($referential->getReturnClass())));
        if (!empty($description)) $doc->setShortDescription($description);
        $name = $referential->functionName();
        if ($this->class->hasMethod($name)) $name = $name.model_name(str_rand(special_xters: false));
        return (new MethodGenerator($name))
            ->setDocBlock($doc)
            ->setBody($body);
    }

    private function one2OneMethod(DBReferential $referential)
    {
        return $this->referentialMethod(
            $referential,
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name),
            "return \$this->hasOne(".model_name($referential->referenced_table_name)."::class, '{$referential->referenced_column_name}', '{$referential->column_name}');"
        );
    }

    public function one2One()
    {
        foreach ($this->connection->One2One($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->one2OneMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
        }
        return $this;
    }

    private function belongsToMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->belongsTo(".model_name($referential->referenced_table_name)."::class, '{$referential->column_name}', '{$referential->referenced_column_name}');",
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function belongsTo()
    {
        foreach ($this->connection->BelongsTo($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->belongsToMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
            if (!$this->class->hasUse($referential->getReturnClass())) $this->class->addUse($referential->getReturnClass());
        }
        return $this;
    }

    private function belongsToManyMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->belongsToMany(".model_name($referential->referenced_table_name)."::class, '{$referential->column_name}', '{$referential->referenced_column_name}');",
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function belongsToMany()
    {
        foreach ($this->connection->belongsToMany($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->belongsToManyMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
            if (!$this->class->hasUse($referential->getReturnClass())) $this->class->addUse($referential->getReturnClass());
        }
        return $this;
    }

    private function oneToManyMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->hasMany(".model_name($referential->referenced_table_name)."::class, '{$referential->referenced_column_name}', '{$referential->column_name}');",
            "Get ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name));
    }

    public function one2Many()
    {
        foreach ($this->connection->One2Many($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->oneToManyMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
            if (!$this->class->hasUse($referential->getReturnClass())) $this->class->addUse($referential->getReturnClass());
        }
        return $this;
    }

    private function oneThroughMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->hasOneThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '{$referential->through_column_name}', '{$referential->referenced_column_name}', '{$referential->column_name}', '{$referential->through_ref_column_name}');",
            "Get ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function oneThrough()
    {
        foreach ($this->connection->oneThrough($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->oneThroughMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
            if (!$this->class->hasUse($referential->getReturnClass())) $this->class->addUse($referential->getReturnClass());
        }
        return $this;
    }

    private function manyThroughMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->hasManyThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '{$referential->through_column_name}', '{$referential->referenced_column_name}', '{$referential->column_name}', '{$referential->through_ref_column_name}');",
            "Get ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name));
    }

    public function manyThrough()
    : static
    {
        foreach ($this->connection->manyThrough($this->table->name) as $referential) {
            if (!LarEloquent::validTable($referential->referenced_table_name)) continue;
            $this->class->addMethodFromGenerator($this->manyThroughMethod($referential))
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($referential->referenced_table_name))
                        ->getDocBlock()->setTag(DocProperty::referentialTag($referential));
            if (!$this->class->hasUse($referential->getReturnClass())) $this->class->addUse($referential->getReturnClass());
        }
        return $this;
    }

    private function morphToMethod(Polymorphic $polymorphic)
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($polymorphic->getMorphToClass())))
            ->setShortDescription("Get associated with the ".model_name($polymorphic->table_name));
        return (new MethodGenerator($polymorphic->actionName()))
            ->setDocBlock($doc)
            ->setBody("return \$this->morphTo('{$polymorphic->morph_name}');");
    }

    public function morphTo()
    {
        foreach (Morpher::morphers($this->connection, $this->table->name) as $polymorphic) {
            ProviderBoot::addMorph($polymorphic->referencedTables());
            foreach ($polymorphic->getReturnableClasses() as $returnableClass) {
                $this->class->addUse($returnableClass);
            }
            $this->class->addMethodFromGenerator($this->morphToMethod($polymorphic))
                        ->addUse($polymorphic->getMorphToClass())
                        ->getDocBlock()->setTag(DocProperty::polymorphicRefTag($polymorphic));
        }
        return $this;
    }

    private function morphManyMethod(Polymorphic $polymorphic)
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($polymorphic->getMorphManyClass())))
            ->setShortDescription("Get ".model_name(in_plural($polymorphic->table_name))." associated with the ".model_name($this->table->name));
        return (new MethodGenerator(method_name(in_plural($polymorphic->table_name))))
            ->setDocBlock($doc)
            ->setBody("return \$this->morphMany(".model_name($polymorphic->table_name)."::class, '{$polymorphic->actionName()}');");
    }

    public function morphMany()
    {
        foreach (Morpher::morphs($this->connection, $this->table->name) as $polymorphic) {
            $this->class->addMethodFromGenerator($this->morphManyMethod($polymorphic))
                        ->addUse($polymorphic->getMorphManyClass())
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($polymorphic->table_name))
                        ->getDocBlock()->setTag(DocProperty::polymorphicManyTag($polymorphic));
        }
        return $this;
    }

    private function parseColumns()
    {
        $this->columns = iterator_to_array($this->connection->Columns($this->table->name));
        foreach ($this->columns as $column) {
            if (DataType::DATETIME == $column->PhpDataType()) $this->class->addUse(Carbon::class);
            if ($column->isEnum()) {
                ColumnEnum::Write($column);
                $this->class->addUse($column->enumClass());
            }
            $this->class->getDocBlock()->setTag($column->docPropertyTag());
            if (!isset($this->primaryCol) && $column->is_primary) $this->primaryCol = $column;
            if (LarEloquent::config()->constant_column_names) {
                $this->class->addConstantFromGenerator($column->constantProperty());
            }

            if (!isset($this->createdCol) && $column->isCreatedColumn()) {
                if (!str_equal('created_at', ($this->createdCol = $column)->column_name)) {
                    $this->class->addConstant('created_at', $this->createdCol->column_name, true);
                }
            }
            if (!isset($this->updatedCol) && $column->isUpdatedColumn()) {
                if (!str_equal('updated_at', ($this->updatedCol = $column)->column_name)) {
                    $this->class->addConstant('updated_at', $this->updatedCol->column_name, true);
                }
            }
            if (!isset($this->deletedCol) && $column->isDeletedColumn()) {
                if (!str_equal('deleted_at', ($this->deletedCol = $column)->column_name)) {
                    $this->class->addConstant('deleted_at', $this->deletedCol->column_name, true);
                }
            }
        }
        return $this;
    }

    private function timestamps()
    {
        if (!isset($this->createdCol, $this->updatedCol)) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('timestamps', false))
                    ->setDocBlock((new DocBlockGenerator('Indicates if the model should be timestamped.'))
                                      ->setTag((new VarTag('timestamps', 'bool')))));
        }
        return $this;
    }

    private function table_name()
    {
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('table', $this->table->name))
                ->setDocBlock((new DocBlockGenerator('Table associated with model.'))
                                  ->setTag((new VarTag('table', 'string'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function primary_key()
    {
        if (!isset($this->primaryCol)) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('primaryKey', $this->primaryCol->column_name))
                ->setDocBlock((new DocBlockGenerator('Primary Key associated with model.'))
                                  ->setTag((new VarTag('primaryKey', 'string'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        if (!$this->primaryCol->increments) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('incrementing', $this->primaryCol->increments))
                    ->setDocBlock((new DocBlockGenerator('Indicate if Primary Key is auto-incrementing.'))
                                      ->setTag((new VarTag('incrementing', 'bool'))))
                    ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        }
        if (DataType::INT != $this->primaryCol->PhpDataType()) {
            $this->class->addPropertyFromGenerator(
                (new PropertyGenerator('keyType', $this->primaryCol->PhpDataType()->value))
                    ->setDocBlock((new DocBlockGenerator('Data type of Primary Key that\'s auto-incrementing.'))
                                      ->setTag((new VarTag('keyType', 'string'))))
                    ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        }
        return $this;
    }

    private function date_format()
    {
        if (!LarEloquent::config()->date_format) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('dateFormat', LarEloquent::config()->date_format))
                ->setDocBlock((new DocBlockGenerator('The storage format of the model\'s date columns.'))
                                  ->setTag((new VarTag('dateFormat', 'string'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function connection()
    {
        if (!LarEloquent::config()->define_connection) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('connection', $this->connection->name))
                ->setDocBlock((new DocBlockGenerator('The database connection that should be used by the model.'))
                                  ->setTag((new VarTag('connection', 'string'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function attributes()
    {
        if (0 >= (count($defaults = array_filter($this->columns, function(DBColumn $col){ return null !== $col->column_default && null != $col->defaultValue(); })))) return $this;
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('attributes', array_combine(array_map(fn(DBColumn $col) => $col->column_name, $defaults), array_map(fn(DBColumn $col) => $col->defaultValue(), $defaults))))
                ->setDocBlock((new DocBlockGenerator('The model\'s default values for attributes.'))
                                  ->setTag((new VarTag('attributes', 'array'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        return $this;
    }

    private function typeCasts()
    {
        $casts = [];
        foreach ($this->columns as $column) {
            $cast = ValueCast::getCast($column);
            if (empty($cast)) continue;
            if (str_equal('array', $cast)) $cast = AsArrayObject::class;
            if (class_exists($cast)) $this->class->addUse($cast);
            $casts[$column->column_name] = $cast;
        }
        $this->class->addPropertyFromGenerator(
            (new PropertyGenerator('casts', array_filter($casts)))
                ->setDocBlock((new DocBlockGenerator('The attributes that should be cast.'))
                                  ->setTag((new VarTag('casts', 'array'))))
                ->setFlags([PropertyGenerator::FLAG_PROTECTED]));
        return $this;
    }

    public static function Write(DBConnection $connection, DBTable $table)
    : Model
    {
        return (new Model($table, $connection))
            ->parseColumns()
            ->table_name()
            ->timestamps()
            ->primary_key()
            ->date_format()
            ->connection()
            ->attributes()
            ->typeCasts()
            ->one2One()
            ->belongsTo()
            ->belongsToMany()
            ->one2Many()
            ->oneThrough()
            ->manyThrough()
            ->morphTo()
            ->morphMany()->_write();
    }
}