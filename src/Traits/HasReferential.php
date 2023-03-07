<?php

namespace Angujo\Lareloquent\Traits;

use Angujo\Lareloquent\Factory\Morpher;
use Angujo\Lareloquent\Factory\ProviderBoot;
use Angujo\Lareloquent\Factory\Relationship;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Models\Polymorphic;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use function Angujo\Lareloquent\in_plural;
use function Angujo\Lareloquent\model_name;

trait HasReferential
{
    public function parseReferential()
    {
        $this->referentials = array_merge(
              iterator_to_array($this->connection->one2One($this->table->name))
            , iterator_to_array($this->connection->BelongsTo($this->table->name))
            , iterator_to_array($this->connection->belongsToMany($this->table->name))
            , iterator_to_array($this->connection->one2Many($this->table->name))
            , iterator_to_array($this->connection->oneThrough($this->table->name))
            , iterator_to_array($this->connection->manyThrough($this->table->name)));
        foreach ($this->referentials as $referential) {
            Relationship::loadMethod($this->class, $referential);
        }
        return $this;
    }

    private function morphToMethod(Polymorphic $polymorphic)
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($polymorphic->getMorphToClass())))
            ->setShortDescription("Get associated with the ".model_name($polymorphic->table_name));
        return (new MethodGenerator($polymorphic->toName()))
            ->setDocBlock($doc)
            ->setBody("return \$this->morphTo('$polymorphic->morph_name');");
    }

    public function morphTo()
    : static
    {
        foreach (Morpher::morphers($this->connection, $this->table->name) as $polymorphic) {
            foreach ($polymorphic->getReturnableClasses() as $returnableClass) {
                $this->class->addUse($returnableClass);
            }
            $this->class->addMethodFromGenerator($this->morphToMethod($polymorphic))
                        ->addUse($polymorphic->getMorphToClass())
                        ->getDocBlock()->setTag($polymorphic->getToDocProperty());
        }
        return $this;
    }

    private function morphManyMethod(Polymorphic $polymorphic)
    : MethodGenerator
    {
        $doc = (new DocBlockGenerator())
            ->setTag(GeneralTag::returnTag(basename($polymorphic->getMorphManyClass())))
            ->setShortDescription("Get ".model_name(in_plural($polymorphic->table_name))." associated with the ".model_name($this->table->name));
        return (new MethodGenerator($polymorphic->manyName()))
            ->setDocBlock($doc)
            ->setBody("return \$this->morphMany(".model_name($polymorphic->table_name)."::class, '{$polymorphic->morph_name}');");
    }

    public function morphMany()
    : static
    {
        foreach (Morpher::morphs($this->connection, $this->table->name) as $polymorphic) {
            if ($this->class->hasMethod($polymorphic->manyName())) continue;
            $this->class->addMethodFromGenerator($this->morphManyMethod($polymorphic))
                        ->addUse($polymorphic->getMorphManyClass())
                        ->addUse(LarEloquent::config()->namespace.'\\'.model_name($polymorphic->table_name))
                        ->addUse(Collection::class)
                        ->getDocBlock()->setTag($polymorphic->getManyDocProperty());
        }
        return $this;
    }

}