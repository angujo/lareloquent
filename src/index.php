<?php

include '../vendor/autoload.php';

use Angujo\Lareloquent\LarEloquent;

echo 'Starting off...';
echo \Angujo\Lareloquent\Path::$ROOT;

$lare=new LarEloquent();
$lare->SetModels();