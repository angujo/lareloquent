<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use Angujo\Lareloquent\Traits\HasLaravelProperties;
use Angujo\Lareloquent\Traits\HasReferential;
use function Angujo\Lareloquent\model_name;

abstract class EloqModel extends FileCreator
{
    use HasReferential, HasLaravelProperties;

    private DBColumn $primaryCol;
    private DBColumn $createdCol;
    private DBColumn $updatedCol;
    private DBColumn $deletedCol;

    private DBTable      $table;
    private DBConnection $connection;

    /** @var array|DBColumn[] */
    public array $columns = [];

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

    public static function Write(DBConnection $connection, DBTable $table)
    : static
    {
        return (new static($table, $connection))
            ->parseColumns()
            ->table_name()
            ->connection()
            ->hidden()
            ->timestamps()
            ->primary_key()
            ->date_format()
            ->attributes()
            ->typeCasts()
            ->localScopes()
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