<?php


include '../vendor/autoload.php';

use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use function Angujo\Lareloquent\in_singular;
use function Angujo\Lareloquent\model_name;

defined('LARELOQ_TEST') || define('LARELOQ_TEST', true);

echo "Starting off...\n";

$n='account_uses';

// var_dump((in_singular($n)));die;


$lare = new LarEloquent(DBConnection::fromDefault());
$lare->setModels(function(DBTable $table){ echo "$table->name\n"; });
$lare->setExtensions();

//var_dump(\Angujo\Lareloquent\str_rand(10));