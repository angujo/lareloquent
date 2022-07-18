<?php

namespace Angujo\Lareloquent;

use Angujo\Lareloquent\Factory\Config;
use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\Factory\Model;

class LarEloquent
{
    private static Config|null $_conf = null;

    private DBConnection $connection;

    public function __construct()
    {
        $this->connection = DBConnection::fromDefault();
    }

    public function SetModels()
    {
        foreach ($this->connection->Tables() as $table) {
            echo "{$table->name}\n";
            Model::Write($this->connection, $table);
        }
    }

    public static function config()
    : Config
    {
        return self::$_conf ?? (self::$_conf = new Config());
    }
}