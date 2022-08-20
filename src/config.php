<?php

use Angujo\Lareloquent\Framework;
use Angujo\Lareloquent\Path;

return
    [
        'framework'               => Framework::CUSTOM,
        /*
         * Class to be used for each and every generated model
         * Ensure it is or extends \Illuminate\Database\Eloquent\Model::class
         */
        'model_class'             => \Eloquent::class,
        /*
         * Directory path to put the models
         */
        'base_dir'                => Path::Combine(ROOT_DIR, 'output'),
        /*
         * Namespace for the models
         */
        'namespace'               => 'App\Models',
        /*
         * Enable composite keys in laravel
         * Currently on testing
         * Allows usage of Model::find($arr=[]) and multiple pri keys
         * If you find yourself using this, reconsider your DB structure
         */
        'composite_keys'          => true,
        /*
         * Name of class to be used in customizing Eloquent to accommodate package changes.
         * E.g. models will be appended static class morphName() to allow access of relation name used.
         */
        'eloquent_extension_name' => 'Extension',
        /*
         * Create abstract classes to act as BASE abstract Class for the tables
         * It is HIGHLY RECOMMENDED to enable this.
         * Enables you to generate models based on changes without affecting your custom code
         * on child models.
         */
        'base_abstract'           => true,
        /*
         * Prefix for the abstract classes
         * Default: Base
         */
        'base_abstract_prefix'    => '',
        /*
         * Set the model connection defined
         */
        'define_connection'       => true,
        /*
         * Add the schema name to the table property
         * Applicable for postgreSQl with multiple schemas other than public.
         * Results to e.g. [schema_name].[table_name]
         */
        'add_table_schema'        => false,
        /*
         * Set the date format for DB, serialization in array or json
         */
        'date_format'             => null,
        /*
         * Add a validation rule to every column and avail as $rules for each model
         */
        'validation_rules'        => true,
        /*
         * Function/Method name for calling the validation rules
         * E.g. if 'validate' then we can later call $user->validate();
         */
        'validation_method'       => 'validate',
        /*
         * Try and auto validate before user calls save or update methods.
         * If set to FALSE, you can use or call the [validation_method] above e.g. $user->validate(); before calling save
         */
        'validate_on_save'        => true,
        /*
         * Set if you want observers to be set up.
         * If [validate_on_save] = TRUE, [observers] will be assumed to be TRUE, irrespective of value below.
         */
        'observers'               => true,
        /*
         * Directory path to put the models' observers
         * Observers are created once and not overwritten.
         * If validation enabled with option of [validate_on_save] set to TRUE,
         * the method will be initiated inside the observer's "saving" method.
         */
        'observers_dir'           => Path::Combine(ROOT_DIR, 'output', 'Observers'),
        /*
         * Namespace for the observers
         */
        'observer_namespace'      => 'App\Models\Observers',
        /*
         * Suffix for the observer's file and class name
         * E.g. for model User + [observer_suffix='Observer'] = UserObserver[.php]
         */
        'observer_suffix'         => 'Observer',
        /*
         * Enable to add @date on each Base Model every time it is run
         * If set to False, @date will be set on first instance
         */
        'date_base'               => false,
        /*
         * Separate Models based on the database/schema
         * Recommended for cross database/schema system
         */
        'db_directories'          => false,
        /*
         * Set Column names as CONST within the models
         * Allows column names to be called as User::EMAIL for email.
         */
        'constant_column_names'   => true,
        /*
         * When [constant_column_names] is enabled,
         * set the prefix to use.
         * e.g. prefix = 'COL_' then column email becomes User::COL_EMAIL
         */
        'constant_column_prefix'  => null,
        /*
         * Column names that are used for soft delete.
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'soft_delete_columns'     => ['deleted_at', 'deleted'],
        /*
         * Column names to mark as create columns
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'create_columns'          => ['created_at', 'created'],
        /*
         * Columns to be used as update
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'update_columns'          => ['updated_at', 'updated'],

        /*
         * Tables to be excluded from model generation
         */
        'excluded_tables'         => ['migrations', 'password_resets', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients', 'oauth_personal_access_clients', 'oauth_refresh_tokens',],
        /*
         *Tables to be run ONLY
         * The reset will be excluded
         */
        'only_tables'             => [],
        /*
         * Pivot tables for a many-to-many relationship
         * E.g. at https://laravel.com/docs/8.x/eloquent-relationships#many-to-many
         * role_user is a pivot table
         * Only set table names
         */
        'pivot_tables'            => ['film_category', 'film_actor'],
        /*
         * Process pivot tables to be part of the model classes.
         * If processed pivot columns accessible through [pivot] as the attribute name,
         * otherwise the pivot class name is used.
         */
        'process_pivot_tables'    => false,
        /*
         * In Laravel 7+ a User table comes with setup and extends Authenticatable
         * This voids IDE intellisense on properties and relations
         * Setting User as Trait is way of trying to circumvent this.
         *
         * Enabling this prepares the system on availing traits for models
         * NOTE: Ensure to resolve any conflict that are already resolved in Trait
         */
        'model_trait'             => true,

        /*
         * Only applies when [model_trait] is TRUE
         * Assist in getting the name of the tables to be set as traits
         * All Column constants are not attainable with this
         */
        'trait_model_tables'      => ['users'],
        /*
         * Instead of extending the [ElolaraModel] the highlighted table base classes will extend respective class
         * Useful for classes such as [User] model where Illuminate\Foundation\Auth\User need to be extended instead of
         * Illuminate\Database\Eloquent\Model
         */
        'custom_extends'          => [],
        /*
         * While naming relations you need to select the order in which the names will be picked.
         * Ordering should start with most preferred.
         * Can only contain any of three entries; [column],[table],[constraint]
         * [column]
         * The column name will be used to identify the relation name
         * @see column_relation_pattern, relation_remove_prx and relation_remove_sfx
         * [table]
         * The target table name will be used.
         * [constraint]
         * The foreign key constrain name will be used.
         *
         * The checking and order preference is based on usage.
         * E.g. for column [manager_user_id] referencing table [users] with constraint [managers_user_id_foreign],
         * will be processed by checking if relation [manager_user] has been created by another relation,
         * if used will check if [user] has been created,
         * if used will check if [managers_user_id_foreign] has been used.
         * If all options used, will skip the relation
         */
        'relation_naming'         => ['column', 'table', 'constraint'],
        /*
         * Column naming pattern to auto identify relations for Foreign Keys
         * This will check column names and set relation name based off them.
         * Percentage similarity will be set to 70%
         * Set to empty or null so as not to use.
         * E.g 1: if = [{relation_name}_id] or = [fk_{relation_name}] or = [fk_{relation_name}_id] when column name is user_manager_id then relation name will be userManager
         * i.e. use {relation_name} to indicate which part of column to be used as relation name.
         */
        'column_relation_pattern' => '{relation_name}_id',
        /*
         * Enable creation of relations based on column name.
         * This allows the using only [column_relation_pattern] on the column name to create a relation.
         * To work, the {relation_name} should referenced a table name in singular/plural format.
         * Foreign keys will not be used for further checks
         */
        'column_auto_relate'      => true,
        /*
         * If you wish to rename pivot tables in belongsToMany relation,
         * Set regex for naming pattern below. The name should be in teh table's comment
         * E.g if set as '{pivot:(\w+)}', a table with comment "This is a table comment for {pivot:role_users}" will rename pivot to role_users instead of default pivot
         */
        'pivot_name_regex'        => '{pivot:(\w+)}',
        /*
         * @see https://laravel.com/docs/eloquent-mutators#attribute-casting
         * Type Casting for properties and database values.
         * You can cast using a column name or data type.
         * To cast data type e.g. tinyint(1) to be boolean,
         * start with "type:" followed by the type i.e. "type:tinyint(1)"=>'boolean'
         */
        'type_casts'              => ['type:tinyint(1)' => 'boolean', '%_json' => 'array', '%_array' => 'array', 'is_%' => 'boolean',
                                      'type:date'       => 'date:Y-m-d', 'type:datetime' => 'datetime:Y-m-d H:i:s'],
        /*
         * Overwrite files during generation.
         * Will be overwritten by the -f(--force) option in artisan cli
         * Need to be explicitly called on console to be implemented,
         * otherwise the value below is ignored
         */
        'overwrite_models'        => false,
        /*
         * Fully import classes even on same namespace (FQDN)
         */
        'full_namespace_import'   => false,
        /*
         * Indicate which columns to hide by default on specific tables
         * Set the table name as key and columns as array values
         * E.g. 'users'=>['password', 'remember_token',]
         */
        'hidden_columns'          => ['users' => ['password', 'remember_token',]],
    ];
