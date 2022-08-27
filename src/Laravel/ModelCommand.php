<?php
/**
 * @author       bangujo ON 2021-04-18 02:49
 * @project      lareloquent
 * @ide          PhpStorm
 * @originalFile ModelCommand.php
 */

namespace Angujo\Lareloquent\Laravel;


use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Illuminate\Console\Command;

/**
 * Class ModelCommand
 *
 * @package Angujo\Lareloquent\Laravel
 */
class ModelCommand extends Command
{
    protected $signature = LarEloquent::LM_APP_NAME.':generate
                            {--f|force : Force overwrite of Base and Model files (not recommended)}
                            {--m|migrate : Perform migration first}
                            {--c|connection= : Connection to use}
                            {--d|database= : Database to work on}';

    protected $description = 'Parse DB schema tables into models';

    /** @var Factory */
    private Factory     $factory;
    private bool        $migrate   = false;
    private string|null $conn_name = null;
    private string|null $database  = null;
    private bool        $force     = false;

    public function __construct(Factory $factory)
    {
        parent::__construct();
        $this->factory = $factory;
    }

    public function handle()
    {
        $this->singleCommand();
        $this->processCommand();
    }

    private function singleCommand()
    {
        $this->force     = $this->option('force');
        $this->migrate   = $this->option('migrate');
        $this->conn_name = $this->option('connection') ?? $this->conn_name;
        $this->database  = $this->option('database') ?? $this->database;
    }

    private function processCommand()
    {
        if ($this->migrate && 0 !== ($exitCode = \Artisan::call('migrate --verbose'))) {
            return $exitCode;
        }
        if ($this->force && $this->confirm('Do you wish to overwrite all models?(All customized changes will be lost!)')) {
            LarEloquent::config()->overwrite = true;
        }
        $this->setConfigs();
        $this->factory->runSchema($this->output, DBConnection::fromConfig());

        return 0;
        // var_dump(Config::all());
    }

    private function setConfigs()
    {
        LarEloquent::config()->command['name']     = $conn_name = ($this->conn_name ?? config('database.default'));
        LarEloquent::config()->command['dbms']     = config("database.connections.{$conn_name}.driver");
        LarEloquent::config()->command['host']     = config("database.connections.{$conn_name}.host");
        LarEloquent::config()->command['dbname']   = $this->database ?? config("database.connections.{$conn_name}.database");
        LarEloquent::config()->command['username'] = config("database.connections.{$conn_name}.username");
        LarEloquent::config()->command['password'] = config("database.connections.{$conn_name}.password");
    }
}