<?php

include '../vendor/autoload.php';

use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;

echo "Starting off...\n";

$lare = new LarEloquent(DBConnection::fromDefault());
$lare->SetModels(function(DBTable $table){ echo "$table->name\n"; });

//var_dump(\Angujo\Lareloquent\str_rand(10));