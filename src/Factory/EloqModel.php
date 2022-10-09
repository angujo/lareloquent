<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use Angujo\Lareloquent\Traits\HasLaravelProperties;
use Angujo\Lareloquent\Traits\HasReferential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use function Angujo\Lareloquent\model_name;

abstract class EloqModel extends FileCreator
{
    use HasReferential, HasLaravelProperties;

    private DBColumn $primaryCol;
    private DBColumn $createdCol;
    private DBColumn $updatedCol;
    private DBColumn $deletedCol;

    public DBTable       $table;
    private DBConnection $connection;

    /** @var array|DBColumn[] */
    public array $columns = [];
    /** @var array|DBReferential[] */
    public array $referentials   = [];
    public bool  $has_recursives = false;

    public function __construct(DBTable $table, DBConnection $connection)
    {
        $this->table        = $table;
        $this->table_name   = $table->name;
        $this->name         = model_name(LarEloquent::config()->base_abstract_prefix.'_'.$this->table->name);
        $this->connection   = $connection;
        $this->parent_class = array_key_exists($this->table->name, LarEloquent::config()->custom_extends) && !empty(LarEloquent::config()->custom_extends[$table->name]) && is_string(LarEloquent::config()->custom_extends[$table->name]) ? LarEloquent::config()->custom_extends[$table->name] : LarEloquent::config()->model_class;
        $this->namespace    = LarEloquent::config()->namespace.'\\'.model_name(LarEloquent::config()->base_abstract_prefix);
        $this->dir          = Path::Combine(LarEloquent::config()->base_dir, model_name(LarEloquent::config()->base_abstract_prefix));
        parent::__construct();
    }

    protected function factoryMethod()
    {
        if (!LarEloquent::config()->factories || is_a($this, TraitModel::class)) return $this;
        $fact_class = implode('\\', [LarEloquent::config()->factories_namespace, model_name($this->table_name).'Factory']);
        $this->class->addMethodFromGenerator(
            (new MethodGenerator('newFactory'))
                ->setStatic(true)
                ->setBody('return '.basename($fact_class).'::new();')
                ->setDocBlock(
                    (new DocBlockGenerator('Create a new factory instance for the '.basename(model_name($this->table_name)).' model.'))
                        ->setTag(new ReturnTag('Factory'))
                ))
                    ->addUse($fact_class)
                    ->addUse(Factory::class)
                    ->addUse(HasFactory::class)
                    ->addTrait('HasFactory');
        return $this;
    }

    public static function Write(DBConnection $connection, DBTable $table)
    : static
    {
        return (new static($table, $connection))
            ->parseColumns()
            ->table_name()
            ->connection()
            ->hidden()
            ->guarded()
            ->timestamps()
            ->primary_key()
            ->date_format()
            ->attributes()
            ->typeCasts()
            ->factoryMethod()
            ->localScopes()
            ->parseReferential()
            ->morphTo()
            ->morphMany()->_write();
    }
}