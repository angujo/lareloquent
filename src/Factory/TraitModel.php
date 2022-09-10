<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Models\DBTable;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\TraitGenerator;

class TraitModel extends EloqModel
{
    protected function __construct(DBTable $table, DBConnection $connection)
    {
        parent::__construct($table, $connection);
        $this->class = new TraitGenerator($this->name);
        if (!empty($this->namespace)) $this->class->setNamespaceName($this->namespace);
        $this->class->setAbstract(true)
                    ->setDocBlock((new DocBlockGenerator('The Table mapping for model.'))
                                      ->setLongDescription('This is the base model for direct mapping of the DB table.'));
    }

}