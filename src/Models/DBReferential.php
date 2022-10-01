<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\Enums\Referential;
use Closure;
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
    public string|null $through_table_name;
    public string|null $through_column_name;
    public string|null $through_ref_column_name;
    public string      $table_name;
    public string      $column_name;
    public string      $referenced_table_name;
    public string      $referenced_column_name;
    /** @ignore */
    // public string $other_columns = '';

    private Referential $ref;
    private ?string     $func_name  = null;
    private ?Closure    $name_check = null;

    public function __construct(Referential $referential)
    {
        $this->ref = $referential;
    }

    public function functionName()
    : string
    {
        return $this->func_name ?? ($this->func_name = method_name($this->accessName()));
    }

    public function setFunctionName(string $name)
    : static
    {
        $this->func_name = method_name(
            match ($this->ref) {
                Referential::ONE2ONE, Referential::ONE_THROUGH, Referential::BELONGS_TO => in_singular($name),
                default => in_plural($name)
            });
        return $this;
    }

    private function accessName()
    : string
    {
        switch ($this->ref) {
            case Referential::ONE_THROUGH:
                return implode('_', [in_singular($this->fromColumnName(true)), in_singular($this->referenced_table_name)]);
            case Referential::BELONGS_TO_MANY:
                return in_plural(preg_replace('/_id(\s+)?$/', '', $this->through_ref_column_name));
            case Referential::BELONGS_TO:
                return in_singular($this->fromColumnName());
            case Referential::ONE2MANY:
                return in_plural((!str_equal($col_n = in_singular($this->fromColumnName(true)), in_singular($this->table_name))) ? implode('_', [$col_n, $this->referenced_table_name]) : $this->referenced_table_name);
            case Referential::ONE2ONE:
                if (!str_equal($col_n = in_singular($this->fromColumnName(true)), in_singular($this->table_name))) return implode('_', [$col_n, in_singular($this->referenced_table_name)]);
                return in_singular($this->referenced_table_name);
            case Referential::MANY_THROUGH:
        }
        return in_plural($this->referenced_table_name);
    }

    private function fromColumnName($ref = false)
    : string
    {
        return col_name_reference($ref ? $this->referenced_column_name : $this->column_name);
    }

    public function getReturnClass()
    : ?string
    {
        return match ($this->ref) {
            Referential::ONE2ONE => HasOne::class,
            Referential::BELONGS_TO => BelongsTo::class,
            Referential::BELONGS_TO_MANY => BelongsToMany::class,
            Referential::ONE2MANY => HasMany::class,
            Referential::ONE_THROUGH => HasOneThrough::class,
            Referential::MANY_THROUGH => HasManyThrough::class,
        };
    }

    public function getDataTypeClass()
    : array|string
    {
        return $this->isCollection() ? [model_name(in_plural($this->referenced_table_name)).'[]', 'Collection'] : model_name(in_singular($this->referenced_table_name));
    }

    public function isCollection()
    {
        return in_array($this->ref, [Referential::ONE2MANY, Referential::BELONGS_TO_MANY, Referential::MANY_THROUGH]);
    }

    public function getTagDocProperty()
    : PropertyTag
    {
        return (new PropertyTag($this->functionName()))
            ->setTypes($this->getDataTypeClass());
    }

    /**
     * @return Referential
     */
    public function getRef()
    : Referential
    {
        return $this->ref;
    }

}