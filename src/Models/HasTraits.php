<?php

namespace Angujo\Lareloquent\Models;

trait HasTraits
{
    /** @var array|string[] */
    private array $traits = [];

    protected function addTrait($trait)
    : static
    {
        if (is_string($trait)) $this->traits[] = $trait;
        if (is_array($trait)) $this->traits = [...$this->traits, ...$trait];
        return $this;
    }

    public function GetTraits()
    : array
    {
        if (empty($this->traits) && method_exists($this, 'setTraits')) $this->setTraits();
        return $this->traits;
    }
}