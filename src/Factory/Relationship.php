<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\RecursiveMethod;
use Angujo\Lareloquent\Enums\Referential;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\GeneralTag;
use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\TraitGenerator;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\model_name;

class Relationship
{
    private DBReferential $referential;
    private string        $path;

    public function __construct(DBReferential $DBReferential)
    {
        $this->referential = $DBReferential;
        $this->path        = LarEloquent::config()->namespace.'\\'.model_name($this->referential->referenced_table_name);
        if (in_array($this->referential->functionName(), array_column(RecursiveMethod::cases(), 'value'))) {
            $this->referential->setFunctionName($this->referential->functionName().'_'.$this->referential->referenced_table_name);
        }
    }

    public function getMethod()
    : MethodGenerator
    {
        return match ($this->referential->getRef()) {
            Referential::ONE2ONE => $this->one2OneMethod(),
            Referential::BELONGS_TO => $this->belongsToMethod(),
            Referential::BELONGS_TO_MANY => $this->belongsToManyMethod(),
            Referential::ONE2MANY => $this->oneToManyMethod(),
            Referential::ONE_THROUGH => $this->oneThroughMethod(),
            Referential::MANY_THROUGH => $this->manyThroughMethod(),
        };
    }

    public function getUses()
    : array
    {
        return array_filter([$this->referential->getReturnClass(), $this->path]);
    }

    public function getTagProperty()
    : PropertyTag
    {
        return $this->referential->getTagDocProperty();
    }

    private function referentialMethod(DBReferential $referential, string $body, string $description = '')
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($referential->getReturnClass())));
        if (!empty($description)) $doc->setShortDescription($description);
        return (new MethodGenerator($referential->functionName()))
            ->setDocBlock($doc)
            ->setBody($body);
    }

    private function one2OneMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->hasOne(".model_name($this->referential->referenced_table_name)."::class, '{$this->referential->referenced_column_name}', '{$this->referential->column_name}');",
            "Get the ".model_name($this->referential->referenced_table_name)." associated with the ".model_name($this->referential->table_name)
        );
    }


    private function belongsToMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->belongsTo(".model_name($this->referential->referenced_table_name)."::class, '{$this->referential->column_name}', '{$this->referential->referenced_column_name}');",
            "Get the ".model_name($this->referential->referenced_table_name)." associated with the ".model_name($this->referential->table_name));
    }


    private function belongsToManyMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->belongsToMany(".model_name($this->referential->referenced_table_name)."::class, '{$this->referential->column_name}', '{$this->referential->referenced_column_name}');",
            "Get the ".model_name($this->referential->referenced_table_name)." associated with the ".model_name($this->referential->table_name));
    }


    private function oneToManyMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->hasMany(".model_name($this->referential->referenced_table_name)."::class, '{$this->referential->referenced_column_name}', '{$this->referential->column_name}');",
            "Get ".model_name(in_plural($this->referential->referenced_table_name))." associated with the ".model_name($this->referential->table_name));
    }


    private function oneThroughMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->hasOneThrough(".model_name($this->referential->referenced_table_name)."::class, ".model_name($this->referential->through_table_name)."::class, '{$this->referential->through_column_name}', '{$this->referential->referenced_column_name}', '{$this->referential->column_name}', '{$this->referential->through_ref_column_name}');",
            "Get ".model_name($this->referential->referenced_table_name)." associated with the ".model_name($this->referential->table_name));
    }


    private function manyThroughMethod()
    : MethodGenerator
    {
        return $this->referentialMethod(
            $this->referential,
            "return \$this->hasManyThrough(".model_name($this->referential->referenced_table_name)."::class, ".model_name($this->referential->through_table_name)."::class, '{$this->referential->through_ref_column_name}', '{$this->referential->referenced_column_name}', '{$this->referential->column_name}', '{$this->referential->through_column_name}');",
            "Get ".model_name(in_plural($this->referential->referenced_table_name))." associated with the ".model_name($this->referential->table_name));
    }

    /**
     * @param ClassGenerator|TraitGenerator $class
     * @param DBReferential                 $referential
     *
     * @return void
     * @throws Exception
     */
    public static function loadMethod(ClassGenerator|TraitGenerator $class, DBReferential $referential)
    : void
    {
        if (!LarEloquent::validTable($referential->referenced_table_name)) return;
        $m      = new self($referential);
        $method = $m->getMethod();
        if ($class->hasMethod($method->getName())) {
            $ret = basename($referential->getReturnClass());
            $cl  = basename($m->path);
            $class->getDocBlock()->setTag(GeneralTag::fromContent('skipped', "{$method->getName()} $cl $ret"));
            return;
        }
        foreach ($m->getUses() as $use) {
            $class->addUse($use);
        }
        $class->getDocBlock()->setTag($m->getTagProperty());
        $class->addMethodFromGenerator($method);
    }
}
