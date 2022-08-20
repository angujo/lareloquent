<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\LarEloquent;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;

class DocProperty
{
    public string $comment = '';
    public string $name;
    /** @var string[] */
    public array $dataTypes;

    protected function __construct(){ }

    public static function fromColumn(DBColumn $column)
    : DocProperty
    {
        $m              = new self();
        $m->comment     = $column->column_comment;
        $m->name        = $column->column_name;
        $m->dataTypes[] = $column->dataType();
        if ($column->is_nullable) $m->dataTypes[] = 'null';
        return $m;
    }

    public static function fromReferential(DBReferential $referential, \Closure $getColumn = null)
    : DocProperty
    {
        $m              = new self();
        $m->name        = $referential->functionName();
        $m->dataTypes[] = $referential->getDataTypeClass();
        LarEloquent::addUsage($referential->table_name,LarEloquent::config()->namespace.'\\'.model_name( $referential->referenced_table_name));
        if (is_callable($getColumn) && !is_null($column = $getColumn($referential->referenced_column_name)) && is_a($column, DBColumn::class) && $column->is_nullable) $m->dataTypes[] = 'null';
        return $m;
    }

    public static function fromPolymorphicReferences(Polymorphic $polymorphic)
    : DocProperty
    {
        $m            = new self();
        $m->name      = $polymorphic->actionName();
        $m->dataTypes =$refs= array_map(function($tbl){ return model_name($tbl); }, $polymorphic->referencedTables());
        LarEloquent::addUsage($polymorphic->table_name,...array_map(function($tbl)use($polymorphic){return LarEloquent::config()->namespace.'\\'.model_name( $tbl);},$refs));
        return $m;
    }

    public static function fromPolymorphicMany(Polymorphic $polymorphic,$tbl_name)
    : DocProperty
    {
        $m              = new self();
        $m->name        = method_name(in_plural($polymorphic->table_name));
        $m->dataTypes[] = model_name($polymorphic->table_name).'[]';
        LarEloquent::addUsage($tbl_name,LarEloquent::config()->namespace.'\\'.model_name( $polymorphic->table_name));
        return $m;
    }

    public function __toString()
    {
        return '@property '.implode('|', $this->dataTypes).' $'.$this->name.' '.$this->comment;
    }
}