<?php

namespace Angujo\Lareloquent;

use Angujo\Lareloquent\Factory\BaseRequest;
use Angujo\Lareloquent\Factory\Config;
use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\Factory\Enumer;
use Angujo\Lareloquent\Factory\Model;
use Angujo\Lareloquent\Factory\ModelFactory;
use Angujo\Lareloquent\Factory\Observer;
use Angujo\Lareloquent\Factory\ProviderBoot;
use Angujo\Lareloquent\Factory\Request;
use Angujo\Lareloquent\Factory\Resource;
use Angujo\Lareloquent\Factory\TraitModel;
use Angujo\Lareloquent\Factory\TypeScriptClass;
use Angujo\Lareloquent\Factory\WorkModel;
use Angujo\Lareloquent\Factory\WorkRequest;
use Angujo\Lareloquent\Factory\WorkResource;

class LarEloquent
{
    const LM_AUTHOR   = 'Barrack Angujo<angujomondi@gmail.com>';
    const LM_APP_NAME = 'lareloquent';

    private static Config|null $_conf      = null;
    private static             $excludes   = [];
    private static             $only       = [];
    private static             $valid_tbls = false;

    private DBConnection $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function setExtensions()
    {
        ProviderBoot::Write();
    }

    public function setModels(\Closure $pre = null, \Closure $post = null)
    {
        if (LarEloquent::config()->requests) {
            BaseRequest::Write();
        }
        Enumer::Write($this->connection);
        foreach ($this->connection->Tables() as $table) {
            if ($pre && is_callable($pre)) $pre($table);
            ProviderBoot::addMorph($table->name);
            /** @var TraitModel|Model $model */
            $model = self::config()->model_trait && in_array($table->name, self::config()->trait_model_tables) ? TraitModel::Write($this->connection, $table) : Model::Write($this->connection, $table);
            WorkModel::Write($table);
            if (!$table->is_view) {
                if (LarEloquent::config()->observers) Observer::Write($table);
                if (LarEloquent::config()->requests) {
                    Request::Write($table, $model->columns);
                    WorkRequest::Write($table);
                }
                if (LarEloquent::config()->factories) {
                    ModelFactory::Write($table, $model->columns);
                }
            }
            if (LarEloquent::config()->typescript) {
                TypeScriptClass::Write($model);
            }
            if (LarEloquent::config()->resources) {
                Resource::Write($table, $model->columns);
                WorkResource::Write($table);
            }
            if ($post && is_callable($pre)) $post($table);
        }
    }

    public static function config()
    : Config
    {
        return self::$_conf ?? (self::$_conf = new Config());
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