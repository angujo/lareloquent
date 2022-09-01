<?php

namespace Angujo\Lareloquent\Traits;

use Angujo\Lareloquent\Factory\Morpher;
use Angujo\Lareloquent\Factory\ProviderBoot;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DocProperty;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Models\Polymorphic;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_rand;

trait HasReferential
{

    private function referentialMethod(DBReferential $referential, string $body, string $description = '')
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($referential->getReturnClass())));
        if (!empty($description)) $doc->setShortDescription($description);
        $name = $referential->functionName();
        $this->class->addUse($referential->getReturnClass());
        if ($this->class->hasMethod($name)) $name = $name.model_name(str_rand(special_xters: false));
        return (new MethodGenerator($name))
            ->setDocBlock($doc)
            ->setBody($body);
    }

    private function one2OneMethod(DBReferential $referential)
    : MethodGenerator
    {
        return $this->referentialMethod(
            $referential,
            "return \$this->hasOne(".model_name($referential->referenced_table_name)."::class, '$referential->referenced_column_name', '$referential->column_name');",
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name)
        );
    }

    public function one2One()
    : static
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
            "return \$this->belongsTo(".model_name($referential->referenced_table_name)."::class, '$referential->column_name', '$referential->referenced_column_name');",
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function belongsTo()
    : static
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
            "return \$this->belongsToMany(".model_name($referential->referenced_table_name)."::class, '$referential->column_name', '$referential->referenced_column_name');",
            "Get the ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function belongsToMany()
    : static
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
            "return \$this->hasMany(".model_name($referential->referenced_table_name)."::class, '$referential->referenced_column_name', '$referential->column_name');",
            "Get ".model_name(in_plural($referential->referenced_table_name))." associated with the ".model_name($referential->table_name));
    }

    public function one2Many()
    : static
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
            "return \$this->hasOneThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '$referential->through_column_name', '$referential->referenced_column_name', '$referential->column_name', '$referential->through_ref_column_name');",
            "Get ".model_name($referential->referenced_table_name)." associated with the ".model_name($referential->table_name));
    }

    public function oneThrough()
    : static
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
            "return \$this->hasManyThrough(".model_name($referential->referenced_table_name)."::class, ".model_name($referential->through_table_name)."::class, '$referential->through_column_name', '$referential->referenced_column_name', '$referential->column_name', '$referential->through_ref_column_name');",
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
            ->setBody("return \$this->morphTo('$polymorphic->morph_name');");
    }

    public function morphTo()
    : static
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
    : static
    {
        foreach (Morpher::morphs($this->connection, $this->table->name) as $polymorphic) {
            $this->class->addMethodFromGenerator($this->morphManyMethod($polymorphic))
                        ->addUse($polymorphic->getMorphManyClass())
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($polymorphic->table_name))
                        ->getDocBlock()->setTag(DocProperty::polymorphicManyTag($polymorphic));
        }
        return $this;
    }

}