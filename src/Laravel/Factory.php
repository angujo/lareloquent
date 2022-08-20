<?php
/**
 * @author       bangujo ON 2021-04-18 03:10
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Factory.php
 */

namespace Angujo\Lareloquent\Laravel;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DatabaseSchema;
use Angujo\Elolara\Database\DBMS;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\CoreModel;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\MorphModel;
use Angujo\Elolara\Model\ObserverModel;
use Angujo\Elolara\Model\SchemaModel;
use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class Factory
 *
 * @package Angujo\Elolara\Laravel
 */
class Factory
{
    /** @var ConnectionInterface */
    private $connection;
    private $con_name;
    /** @var ProgressBar */
    public static $BAR;

    public function __construct()
    {

    }

    public function runSchema(OutputStyle $output,DBConnection $connection)
    {
        $dbms    = new DBMS($this->connection);
        $lareloq = new LarEloquent($connection);

        $schema = $dbms->getSchema()
                       ->setExcludedTables(...\Arr::wrap(Config::excluded_tables()))
                       ->setOnlyTables(...\Arr::wrap(Config::only_tables()));

        self::$BAR = $output->createProgressBar(($connection->countTables() * 17) + 5);
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Init');
        self::$BAR->start();
        self::$BAR->setMessage('Preparing dirs...');
        $this->prepareDirs();
        self::$BAR->advance();
        self::$BAR->setMessage('Writing core models...');
        $this->writeCoreModel();
        self::$BAR->advance();
        self::$BAR->setMessage('Writing core morph models...');
        $this->writeCoreMorphModel($schema);
        if (Config::db_directories()) $this->writeSchemaModel();
        self::$BAR->advance();

        $lareloq->SetModels(
            function(
                \Angujo\Lareloquent\Models\DBTable $table){
                self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% {$table->name}: %message%");
                self::$BAR->advance();
                self::$BAR->setMessage("Writing Model...");
            },
            function(\Angujo\Lareloquent\Models\DBTable $table){
                self::$BAR->advance();
            }
        );
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Done!');
        self::$BAR->finish();
    }

    protected function prepareDirs()
    {
        if (!file_exists($md = Config::models_dir())) mkdir($md, 0777, true);
        if (!is_writable($md)) throw new \Exception("'{$md}' is not writeable!");

        if (Config::observers() || Config::validate_on_save()) {
            if (!file_exists($md = Config::observers_dir())) mkdir($md, 0777, true);
            if (!is_writable($md)) throw new \Exception("'{$md}' is not writeable!");
        }

        if (!file_exists($cd = Config::extensions_dir())) mkdir($cd, 0777, true);
        if (!is_writable($cd)) throw new \Exception("'{$cd}' is not writeable!");

        if (Config::base_abstract()) {
            if (!file_exists($dir = Config::abstracts_dir()) || !is_dir($dir)) mkdir($dir, 0777, true);
            if (!is_writable($dir)) throw new \Exception("'{$dir}' is not writeable!");
        }
    }

    protected function writeBaseModel(DBTable $table)
    {
        if (Config::model_trait() && in_array($table->name, Config::trait_model_tables())) {
            $this->writeModel(Model::forTraitTable($table)->setConnection($this->con_name));
        } else $this->writeModel(Model::forBaseTable($table)->setConnection($this->con_name));
    }

    protected function writeModel(Model $model)
    {
        $path = (Config::base_abstract() && $model->base_model ? Config::abstracts_dir() : Config::models_dir()).$model->name.'.php';
        $this->writeObserverModel($model);
        if (!Config::overwrite_models() && false === $model->base_model && file_exists($path)) return;
        file_put_contents($path, (string)$model);
    }

    /**
     * Set the parent Model for all
     * We'll always overwrite depending on config changes.
     * If user want's to update, then a custom file can be set as model_class in config
     * Parent changes can be pushed there.
     */
    protected function writeSchemaModel()
    {
        $model = SchemaModel::load();
        $path  = Config::extensions_dir().$model->name.'.php';
        file_put_contents($path, (string)$model);
    }

    /**
     * Set the parent Model for all
     * We'll always overwrite depending on config changes.
     * If user want's to update, then a custom file can be set as model_class in config
     * Parent changes can be pushed there.
     */
    protected function writeObserverModel(Model $model)
    {
        if ($model->base_model) return;
        $model = ObserverModel::load($model);
        $path  = Config::observers_dir().DIRECTORY_SEPARATOR.$model->name.'.php';
        if (file_exists($path)) return;
        file_put_contents($path, (string)$model);
    }

    protected function writeCoreModel()
    {
        $model = CoreModel::load();
        $path  = Config::extensions_dir().$model->name.'.php';
        file_put_contents($path, (string)$model);
    }

    protected function writeCoreMorphModel(DatabaseSchema $schema)
    {
        $model = MorphModel::core($schema);
        $path  = Config::extensions_dir().$model->name.'.php';
        file_put_contents($path, (string)$model);
    }
}