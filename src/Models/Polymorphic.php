<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\LarEloquent;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class Polymorphic extends DBInterface
{
    public string $morph_name;
    public string $table_name;
    public string $type_column;
    public string $id_column;
    public string $column_comment;
    /** @var array|string[] */
    private $tables = [];

    public function toName()
    {
        $last3_xter = substr($this->morph_name, -3);
        if (str_equal('ate', $last3_xter)) return preg_replace('/ate$/', 'able', $this->morph_name);
        if (str_equal('mit', $last3_xter)) return preg_replace('/mit$/', 'missible', $this->morph_name);
        $last2_xter = substr($this->morph_name, -2);
        if (str_equal('or', $last2_xter)) return preg_replace('/([^aeiou])or$/', '$1able', $this->morph_name);
        if (str_equal('nd', $last2_xter)) return preg_replace('/nd$/', 'nsible', $this->morph_name);
        if (1 == preg_match('/^[clg]e$/', $last2_xter)) goto close;
        $last_xter = substr($this->morph_name, -1);
        if (str_equal('e', $last_xter)) return preg_replace('/e$/', 'able', $this->morph_name);
        close:
        return $this->morph_name.'able';
    }

    public function manyName()
    {
        return method_name(in_plural($this->table_name));
    }

    public function referencedTables()
    {
        return (!empty($this->tables)) ? $this->tables : ($this->tables = array_filter(array_map('trim', explode(',', $this->column_comment))));
    }

    public function isReferenced(string $table_name)
    {
        return in_array($table_name, $this->referencedTables());
    }

    public function getReturnableClasses()
    {
        return array_map(function($tbl_name){ return LarEloquent::config()->namespace.'\\'.model_name($tbl_name); }, $this->referencedTables());
    }

    public function getMorphToClass()
    {
        return MorphTo::class;
    }

    public function getMorphManyClass()
    {
        return MorphMany::class;
    }

    public function getToDocProperty()
    {
        return (new PropertyTag($this->toName()))
            ->setTypes(array_map(function($tbl){ return model_name($tbl); }, $this->referencedTables()));
    }

    public function getManyDocProperty()
    {
        return (new PropertyTag(method_name(in_plural($this->table_name))))
            ->setTypes([model_name($this->table_name).'[]', 'Collection']);
    }
}