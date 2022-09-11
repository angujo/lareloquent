<?php


include '../vendor/autoload.php';

use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;

defined('LARELOQ_TEST') || define('LARELOQ_TEST', true);

echo "Starting off...\n";

$lare = new LarEloquent(DBConnection::fromDefault());
$lare->setModels(function(DBTable $table){ echo "$table->name\n"; });
$lare->setExtensions();

//var_dump(\Angujo\Lareloquent\str_rand(10));