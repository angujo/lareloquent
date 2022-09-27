<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\Framework;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\str_equal;

/**
 * @property Framework   $framework
 * @property string|null $model_class
 * @property string      $base_dir
 * @property string      $namespace
 * @property string      $primary_key_name
 * @property string      $eloquent_extension_dir
 * @property bool        $base_abstract
 * @property string      $base_abstract_prefix
 * @property bool        $define_connection
 * @property bool        $add_table_schema
 * @property string      $date_format
 * @property bool        $observers
 * @property string      $observers_dir
 * @property string      $observer_namespace
 * @property string      $observer_suffix
 * @property bool        $resources
 * @property string      $resources_dir
 * @property string      $resource_namespace
 * @property string      $resource_suffix
 * @property string      $base_resource_prefix
 * @property bool        $enums
 * @property string      $enums_dir
 * @property string      $enum_namespace
 * @property bool        $requests
 * @property string      $requests_dir
 * @property string      $request_namespace
 * @property string      $request_suffix
 * @property string      $base_request_prefix
 * @property bool        $date_base
 * @property bool        $db_directories
 * @property bool        $constant_column_names
 * @property string|null $constant_column_prefix
 * @property string[]    $soft_delete_columns
 * @property string[]    $create_columns
 * @property string[]    $update_columns
 * @property string[]    $parent_columns
 * @property string[]    $excluded_tables
 * @property string[]    $only_tables
 * @property string[]    $pivot_tables
 * @property bool        $model_trait
 * @property bool        $process_pivot_tables
 * @property string[]    $trait_model_tables
 * @property string[]    $custom_extends
 * @property string[]    $type_casts
 * @property bool        $overwrite_models
 * @property string[]    $hidden_columns
 * @property string[]    $local_scopes
 * @property string[]    $guarded_columns
 * @property string[]    $identified_columns
 */
class Config
{
    /** @var array */
    private array $configs = [];

    /** @var array */
    public array $command = [
        'name'     => null,
        'dbms'     => null,
        'host'     => null,
        'dbname'   => null,
        'username' => null,
        'password' => null,
    ];

    public bool $overwrite = false;

    public function __construct()
    {
        if (defined('LARELOQ_TEST')) {
            $this->configs = include(Path::Combine(ROOT_DIR, 'Test', "config-test.php"));
        } else $this->configs = array_merge(include(Path::Combine(BASE_DIR, "config.php")), is_callable('config') ? config(LarEloquent::LM_APP_NAME) : []);
    }

    public function isLaravel()
    : bool
    {
        return Framework::LARAVEL == $this->framework;
    }

    public function __get(string $name)
    {
        $key = strtolower($name);
        if (in_array($key, ['base_abstract_prefix', 'base_resource_prefix', 'base_request_prefix']) && (!isset($this->configs[$key]) || empty($this->configs[$key]))) {
            $this->configs[$key] = 'Base';
        }
        if (str_equal($key, 'eloquent_extension_dir') && (!isset($this->configs[$key]) || empty($this->configs[$key]))) {
            $this->configs[$key] = 'Extensions';// str_rand(10, numbers: false, special_xters: false);
        }
        if (str_equal($key, 'primary_key_name') && (!isset($this->configs[$key]) || empty($this->configs[$key]))) {
            $this->configs[$key] = 'id';// str_rand(10, numbers: false, special_xters: false);
        }
        return $this->configs[$key] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->configs[$name]) && !empty($this->configs[$name]);
    }
}