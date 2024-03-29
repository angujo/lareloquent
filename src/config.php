<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

return
    [
        /*
         * Class to be used for each and every generated model
         * Ensure it is or extends \Illuminate\Database\Eloquent\Model::class
         */
        'model_class' => Model::class,
        /*
         * Directory path to put the models
         */
        'base_dir' => app_path('Models'),
        /*
         * Namespace for the models
         */
        'namespace' => 'App\Models',
        /*
         * Name of directory to be used to hold custom classes used by library.
         * E.g. models will be appended static class morphName() to allow access of relation name used.
         */
        'eloquent_extension_dir' => 'Extension',
        /*
         * Create abstract classes to act as BASE abstract Class for the tables
         * It is HIGHLY RECOMMENDED to enable this.
         * Enables you to generate models based on changes without affecting your custom code
         * on child models.
         */
        'base_abstract' => true,
        /*
         * Prefix for the abstract classes
         * Default: Base
         */
        'base_abstract_prefix' => '',
        /*
         * Set the model connection defined
         */
        'define_connection' => true,
        /*
         * Add the schema name to the table property
         * Applicable for postgresSQl with multiple schemas other than public.
         * Results to e.g. [schema_name].[table_name]
         */
        'add_table_schema' => true,
        /*
         * Set the date format for DB, serialization in array or json
         */
        'date_format' => 'Y-M-d HH:i:s',
        /*
         * Select a method that is used to perform updates other than post.
         * This will separate your request based on whether an update is being done of not.
         * Effective on required parameters that might not be part of request during an update.
         */
        'update_method' => 'PUT',
        /*
         * Enter the prefixes of corresponding columns in a table that indicate the start and end of a period.
         * E.g. start_date and end_date or start_datetime and end_datetime if such format is used the design.
         * Other extensions can be used as long as the prefixes are respected.
         * E.g. start_event_date can be used as well.
         */
        'period_date_prefixes' => ['start_', 'end_'],
        /*
         * Primary Key Name will mostly be used with views. This will flag the column as the primary for a view.
         * Tables can have a different name and easily determined.
         * Default: id
         */
        'primary_key_name' => 'id',
        /*
         * Allow defining scopes for various models.
         * This will add abstract scope methods to be implemented as well as set method doc for easy coding.
         * E.g. ['users'=>['active','expired'],'products'=>'sold',...]
         */
        'local_scopes' => ['actor' => ['popular', 'dead']],
        /*
         * Set if you want resources to be set up.
         */
        'resources' => true,
        /*
         * Directory path to put the models' resources
         */
        'resources_dir' => app_path("Http" . DIRECTORY_SEPARATOR . 'Resources'),
        /*
         * Namespace for the resources
         */
        'resource_namespace' => 'App\Http\Resources',
        /*
         * Suffix for the request's file and class name
         * E.g. for model User + [observer_suffix='Request'] = UserRequest[.php]
         */
        'resource_suffix' => 'Resource',
        /*
         * Prefix for the abstract resource classes
         * This will be used to extend the namespace and add dir for the base resources
         * Default: Base
         */
        'base_resource_prefix' => '',
        /*
         * Set if you want requests to be set up.
         */
        'requests' => true,
        /*
         * Directory path to put the models' requests
         */
        'requests_dir' => app_path('Http' . DIRECTORY_SEPARATOR . 'Requests'),
        /*
         * Namespace for the requests
         */
        'request_namespace' => 'App\Http\Requests',
        /*
         * Suffix for the request's file and class name
         * E.g. for model User + [observer_suffix='Request'] = UserRequest[.php]
         */
        'request_suffix' => 'Request',
        /*
         * Prefix for the abstract request classes
         * This will be used to extend the namespace and add dir for the base requests
         * Default: Base
         */
        'base_request_prefix' => '',
        /*
         * Set if you want observers to be set up.
         * If [validate_on_save] = TRUE, [observers] will be assumed to be TRUE, irrespective of value below.
         */
        'observers' => true,
        /*
         * Directory path to put the models' observers
         * Observers are created once and not overwritten.
         * If validation enabled with option of [validate_on_save] set to TRUE,
         * the method will be initiated inside the observer's "saving" method.
         */
        'observers_dir' => app_path('Models' . DIRECTORY_SEPARATOR . 'Observers'),
        /*
         * Namespace for the observers
         */
        'observer_namespace' => 'App\Models\Observers',
        /*
         * Suffix for the observer's file and class name
         * E.g. for model User + [observer_suffix='Observer'] = UserObserver[.php]
         */
        'observer_suffix' => 'Observer',
        /*
         * Set if you want enums to be set up for the enum data type.
         */
        'enums' => true,
        /*
         * Directory path to put the models' enums
         * Enums will be created for evey column of type enum
         * Table name and column name will be used as the enum name.
         */
        'enums_dir' => app_path('Models' . DIRECTORY_SEPARATOR . 'Enums'),
        /*
         * Namespace for the enums
         */
        'enum_namespace' => 'App\Models\Enums',
        /*
         * This creates model factories that can be used to seed DB during development
         * @see https://laravel.com/docs/eloquent-factories
         * Factories are not overwritten, any subsequent change will need to be manually added.
         * By default value mapping is null and no relationship is considered.
         * These should be done manually during development.
         * To allow extending models, newFactory method will be overwritten and $model property added
         */
        'factories' => true,
        /*
         * Namespace for the factories
         */
        'factories_namespace' => 'Database\Factories',
        /*
         * Directory for the factories
         */
        'factories_dir' => base_path('database' . DIRECTORY_SEPARATOR . 'factories'),
        /*
         * Enable to add @date on each Base Model every time it is run
         * If set to False, @date will be set on first instance
         */
        'date_base' => false,
        /*
         * Separate Models based on the database/schema
         * Recommended for cross database/schema system
         */
        'db_directories' => false,
        /*
         * Set Column names as CONST within the models
         * Allows column names to be called as User::EMAIL for email.
         */
        'constant_column_names' => true,
        /*
         * When [constant_column_names] is enabled,
         * set the prefix to use.
         * e.g. prefix = 'COL_' then column email becomes User::COL_EMAIL
         */
        'constant_column_prefix' => 'COL_',
        /*
         * Column names that are used for soft delete.
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'soft_delete_columns' => ['deleted_at', 'deleted'],
        /*
         * Column names to mark as create columns
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'create_columns' => ['created_at', 'created'],
        /*
         * Columns to be used as update
         * If different naming across tables, add them here.
         * NOTE: No two names should be on same table.
         */
        'update_columns' => ['updated_at', 'updated'],
        /*
         * For a self referencing tables, indicate the column names that are likely to be used
         * This will use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships class
         * FOr more details;
         * @see https://github.com/staudenmeir/laravel-adjacency-list
         * To disable, set the array to empty
         */
        'parent_columns' => ['parent_id',],

        /*
         * Tables to be excluded from model generation
         */
        'excluded_tables' => ['migrations', 'password_resets', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients', 'oauth_personal_access_clients', 'oauth_refresh_tokens',],
        /*
         *Tables to be run ONLY
         * The reset will be excluded
         */
        'only_tables' => [],
        /*
         * Process pivot tables to be part of the model classes.
         * If processed pivot columns accessible through [pivot] as the attribute name,
         * otherwise the pivot class name is used.
         */
        'process_pivot_tables' => false,
        /*
         * Pivot tables for a many-to-many relationship
         * E.g. at https://laravel.com/docs/8.x/eloquent-relationships#many-to-many
         * role_user is a pivot table
         * Only set table names
         */
        'pivot_tables' => ['film_category', 'film_actor'],
        /*
         * In Laravel 7+ a User table comes with setup and extends Authenticate
         * This voids IDE intellisense on properties and relations
         * Setting User as Trait is way of trying to circumvent this.
         *
         * Enabling this prepares the system on availing traits for models
         * NOTE: Ensure to resolve any conflict that are already resolved in Trait
         */
        'model_trait' => true,

        /*
         * Only applies when [model_trait] is TRUE
         * Assist in getting the name of the tables to be set as traits
         * All Column constants are not attainable with this
         */
        'trait_model_tables' => ['users', 'staff'],
        /*
         * Instead of extending the [CoreModel] the highlighted table base classes will extend respective class
         * Useful for classes such as [User] model where Illuminate\Foundation\Auth\User need to be extended instead of
         * Illuminate\Database\Eloquent\Model
         */
        'custom_extends' => ['users' => User::class],
        /*
         * @see https://laravel.com/docs/eloquent-mutators#attribute-casting
         * Type Casting for properties and database values.
         * You can cast using a column name or data type.
         * To cast data type e.g. tinyint(1) to be boolean,
         * start with "type:" followed by the type i.e. "type:tinyint(1)"=>'boolean'
         */
        'type_casts' => ['type:tinyint' => 'boolean', '%_json' => 'array', '%_array' => 'array', 'is_%' => 'boolean',
            'type:date' => 'date:Y-m-d', 'type:datetime' => 'datetime:Y-m-d H:i:s', 'type:timestamp' => 'datetime:Y-m-d H:i:s'],
        /*
         * Overwrite files during generation.
         * Will be overwritten by the -f(--force) option in artisan cli
         * Need to be explicitly called on console to be implemented,
         * otherwise the value below is ignored
         */
        'overwrite_models' => false,
        /*
         * Indicate which columns to hide by default on specific tables
         * Set the table name as key and columns as array values
         * E.g. 'users'=>['password', 'remember_token',]
         */
        'hidden_columns' => ['users' => ['password', 'remember_token',], 'staff' => ['password']],
        /*
         * Indicate which columns to hide by default on specific tables
         * Set the table name as key and columns as array values
         * E.g. 'users'=>['password', 'remember_token',]
         */
        'guarded_columns' => ['users' => ['password', 'remember_token',],],
        /*
         * Help in identifying column values based on a type.
         * E.g. a column can hold arrays i.e. [1,2,3,..] but is of data type string with name hint ending with '_array' therefore ..'array'=>[['name'=>'%_array','type'=>'string']]..
         * Keys can only be any of array, json, image, file, ip,uuid,url,email,mac_address
         * The items should be set in order of priorities to avoid conflicts
         */
        'identified_columns' => [
            'json' => [['name' => '%_json', 'type' => 'string'], ['type' => 'json']],
            'array' => [['name' => '%_array', 'type' => 'string'], ['name' => '%_array', 'type' => 'json'], ['type' => 'set']],
            'image' => [['name' => '%_image', 'type' => 'string'], ['name' => '%_picture', 'type' => 'string']],
            'file' => [['name' => '%_file', 'type' => 'string'],],
            'ip' => [['name' => '%_ip', 'type' => 'string'], ['name' => 'ip_address', 'type' => 'string'],],
            'uuid' => [['name' => '%uuid', 'type' => 'string'],],
            'url' => [['name' => '%url', 'type' => 'string'],],
            'email' => [['name' => '%email', 'type' => 'string'],],
            'mac_address' => [['name' => '%mac_address', 'type' => 'string'],],
        ],
        /*
         * VALIDATION ON REQUEST CLASSES
         * Validations can be added on each and every column in the db table as comments
         * Laravel validations are supported
         * @see https://laravel.com/docs/validation#available-validation-rules
         * comment validations should follow the format, validation:{validation_name:conditions,...;validation_name2:conditions,..;...}
         * All validations should be separated by semicolon (;)
         * E.g. For a table with start (start_date) and end (end_date) dates, column can have validation comment as
         *      start_date Column comment "validation:{lte:end_date}"
         *      end_date Column comment "validation:{gte:start_date}"
         */

        //--------------------------------------------------------TYPESCRIPT-------------------------------------------------
        'typescript' => false,
        /*
         * Indicate whether to produce interfaces or classes.
         * If TRUE, interfaces will be generated, otherwise classes
         * Interfaces will have a suffix 'I' added to them. E.g. users table can have interface IUser
         */
        'typescript_interface' => true,
        'typescript_dir' => base_path('storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ts'),
    ];
