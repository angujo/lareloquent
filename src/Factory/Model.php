<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Models\DBTable;
use Laminas\Code\Generator\DocBlockGenerator;

class Model extends EloqModel
{
    protected function __construct(DBTable $table, DBConnection $connection)
    {
        parent::__construct($table, $connection);
        $this->class->setAbstract(true)
                    ->setDocBlock((new DocBlockGenerator('The Table mapping for model.'))
                                      ->setLongDescription('This is the base model for direct mapping of the DB table.'));
    }
}