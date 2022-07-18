<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Framework;
use Angujo\Lareloquent\Path;

/**
 * @property Framework   $framework
 * @property string|null $model_class
 * @property string      $base_dir
 * @property string      $namespace
 * @property bool        $composite_keys
 * @property string      $eloquent_extension_name
 * @property bool        $base_abstract
 * @property string      $base_abstract_prefix
 * @property bool        $define_connection
 * @property bool        $add_table_schema
 * @property string      $date_format
 * @property bool        $validation_rules
 * @property string      $validation_method
 * @property bool        $validate_on_save
 * @property bool        $observers
 * @property string      $observers_dir
 * @property string      $observer_namespace
 * @property string      $observer_suffix
 * @property bool        $date_base
 * @property bool        $db_directories
 * @property bool        $constant_column_names
 * @property string|null $constant_column_prefix
 * @property string[]    $soft_delete_columns
 * @property string[]    $create_columns
 * @property string[]    $update_columns
 * @property string[]    $excluded_tables
 * @property string[]    $only_tables
 * @property bool        $model_trait
 * @property string[]    $trait_model_tables
 * @property string[]    $custom_extends
 * @property string[]    $relation_naming
 * @property string      $column_relation_pattern
 * @property string      $column_auto_relate
 * @property string      $pivot_name_regex
 * @property string[]    $type_casts
 * @property bool        $overwrite_models
 * @property bool        $full_namespace_import
 * @property string      $hidden_columns
 */
class Config
{
    /** @var array */
    private array $configs = [];

    public function __construct()
    {
        $this->configs = array_merge(include(Path::Combine(BASE_DIR, "config.php")));
    }

    public function isLaravel()
    : bool
    {
        return Framework::LARAVEL == $this->framework;
    }

    public function __get(string $name)
    {
        $key = strtolower($name);
        return $this->configs[$key] ?? null;
    }
}