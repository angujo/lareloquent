<?php

namespace Angujo\Lareloquent\Models;

trait HasUsage
{
    private array $uses = [];

    protected function addUse($use)
    : static
    {
        if (is_string($use)) $this->uses[] = $use;
        if (is_array($use)) $this->uses = [...$this->uses, ...$use];
        return $this;
    }

    public function GetUses()
    {
        if (empty($this->uses) && method_exists($this, 'setUses')) $this->setUses();
        return $this->uses;
    }
}