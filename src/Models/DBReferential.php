<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\Enums\Referential;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use function Angujo\Lareloquent\col_name_reference;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\in_singular;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class DBReferential
{
    use HasUsage;

    public string|null $through_table_name;
    public string|null $through_column_name;
    public string|null $through_ref_column_name;
    public string      $table_name;
    public string      $column_name;
    public string      $other_columns = '';
    public string      $referenced_table_name;
    public string      $referenced_column_name;

    private Referential $ref;
    private ?string     $func_name  = null;
    private ?\Closure   $name_check = null;

    public function __construct(Referential $referential)
    {
        $this->ref = $referential;
    }

    public function pivotColumns()
    {
        return empty($this->other_columns) ? [] : array_map('trim', explode(',', $this->other_columns));
    }

    public function functionName()
    {
        return $this->func_name ?? ($this->func_name = method_name($this->accessName()));
    }

    private function accessName()
    {
        switch ($this->ref) {
            case Referential::ONETHROUGH:
                return implode('_', [in_singular($this->fromColumnName(true)), in_singular($this->referenced_table_name)]);
            case Referential::BELONGSTOMANY:
                return in_plural(preg_replace('/_id(\s+)?$/', '', $this->through_ref_column_name));
            case Referential::BELONGSTO:
                return in_singular($this->fromColumnName());
            case Referential::ONE2MANY:
                return in_plural((!str_equal($col_n = in_singular($this->fromColumnName(true)), in_singular($this->table_name))) ?$col_n : $this->referenced_table_name);
            case Referential::ONE2ONE:
                if (!str_equal($col_n = in_singular($this->fromColumnName(true)), in_singular($this->table_name))) return implode('_', [$col_n, in_singular($this->referenced_table_name)]);
            case Referential::MANYTHROUGH:
            default:
                return $this->referenced_table_name;
        }
    }

    private function fromColumnName($ref = false)
    {
        return col_name_reference($ref ? $this->referenced_column_name : $this->column_name);
    }

    /**
     * @param \Closure|null $name_check
     *
     * @return DBReferential
     */
    public function setNameCheck(?\Closure $name_check)
    : DBReferential
    {
        $this->name_check = $name_check;
        return $this;
    }

    private function fromTableName($ref = false)
    {
        return $ref ? $this->referenced_table_name : $this->table_name;
    }

    public function getReturnClass()
    {
        return match ($this->ref) {
            Referential::ONE2ONE => HasOne::class,
            Referential::BELONGSTO => BelongsTo::class,
            Referential::BELONGSTOMANY => BelongsToMany::class,
            Referential::ONE2MANY => HasMany::class,
            Referential::ONETHROUGH => HasOneThrough::class,
            Referential::MANYTHROUGH => HasManyThrough::class,
            default => null,
        };
    }

    public function getDataTypeClass()
    : ?string
    {
        return match ($this->ref) {
            Referential::BELONGSTO, Referential::ONETHROUGH, Referential::ONE2ONE => model_name(in_singular($this->referenced_table_name)),
            Referential::ONE2MANY, Referential::BELONGSTOMANY, Referential::MANYTHROUGH => model_name(in_plural($this->referenced_table_name)).'[]',
            default => null,
        };
    }

    public function setUses()
    {
        $this->addUse($this->getReturnClass());
    }

    public function getTagDocProperty()
    : PropertyTag
    {
        return (new PropertyTag($this->functionName()))
            ->setTypes($this->getDataTypeClass());
    }
}