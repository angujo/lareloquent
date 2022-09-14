<?php


include '../vendor/autoload.php';

use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use function Angujo\Lareloquent\in_singular;
use function Angujo\Lareloquent\model_name;

defined('LARELOQ_TEST') || define('LARELOQ_TEST', true);

echo "Starting off...\n";

$name = 'account_uses';

$preg       = preg_replace_callback('/([A-Z])([a-z\d]+)$/',
    function($matches){return in_singular($matches[1].$matches[2]); },
                                    preg_replace_callback('/(^|[^A-Za-z\d])([a-zA-Z])([A-Z]+|[a-z\d]+)/', function($matches){ return strtoupper($matches[2]).strtolower($matches[3]); }, $name));
$model_name = in_singular($preg);
var_dump($preg);
die($model_name);

$lare = new LarEloquent(DBConnection::fromDefault());
$lare->setModels(function(DBTable $table){ echo "$table->name\n"; });
$lare->setExtensions();

//var_dump(\Angujo\Lareloquent\str_rand(10));