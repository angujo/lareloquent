<?php

namespace Angujo\Lareloquent;

use Angujo\Lareloquent\Factory\Config;
use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\Factory\Model;
use Angujo\Lareloquent\Factory\Observer;
use Angujo\Lareloquent\Factory\WorkModel;

class LarEloquent
{
    const LM_AUTHOR   = 'Barrack Angujo<angujomondi@gmail.com>';
    const LM_APP_NAME = 'lareloquent';

    private static Config|null $_conf      = null;
    private static             $uses       = [];
    private static             $excludes   = [];
    private static             $only       = [];
    private static             $valid_tbls = false;

    private DBConnection $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function SetModels(\Closure $pre = null, \Closure $post = null)
    {
        foreach ($this->connection->Tables() as $table) {
            if ($pre && is_callable($pre)) $pre($table);
            Model::Write($this->connection, $table);
            WorkModel::Write($table);
            if (LarEloquent::config()->observers) Observer::Write($table);
            if ($post && is_callable($pre)) $post($table);
        }
    }

    public static function config()
    : Config
    {
        return self::$_conf ?? (self::$_conf = new Config());
    }

    public static function addUsage($tbl_name, ...$usage)
    {
        if (!isset(self::$uses[$tbl_name])) self::$uses[$tbl_name] = [];
        self::$uses[$tbl_name] = [...self::$uses[$tbl_name], ...$usage];
    }

    public static function getUsages($tbl_name)
    {
        return self::$uses[$tbl_name] ?? [];
    }

    public static function validTable($tbl_name)
    {
        if (false === self::$valid_tbls) {
            self::$excludes = (!LarEloquent::config()->process_pivot_tables && isset(LarEloquent::config()->pivot_tables)) ? LarEloquent::config()->pivot_tables : [];
            if (isset(LarEloquent::config()->only_tables)) {
                self::$only = array_diff(LarEloquent::config()->only_tables, self::$excludes);
            } else {
                self::$excludes = [...self::$excludes, ...(isset(LarEloquent::config()->excluded_tables) ? LarEloquent::config()->excluded_tables : [])];
            }
            self::$valid_tbls = true;
        }
        return (!empty(self::$only) && in_array($tbl_name, self::$only)) || !in_array($tbl_name, self::$excludes);
    }
}