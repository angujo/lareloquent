<?php
/**
 * @author       bangujo ON 2021-04-18 03:10
 * @project      lareloquent
 * @ide          PhpStorm
 * @originalFile Factory.php
 */

namespace Angujo\Lareloquent\Laravel;


use Angujo\Lareloquent\Factory\DBConnection;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class Factory
 *
 * @package Angujo\LarEloquent\Laravel
 */
class Factory
{
    /** @var ProgressBar */
    public static ProgressBar $BAR;

    public function __construct(){ }

    public function runSchema(OutputStyle $output, DBConnection $connection)
    {
        $lareloq = new LarEloquent($connection);

        self::$BAR = $output->createProgressBar(($connection->countTables() * 17) + 5);
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Init');
        self::$BAR->start();
        self::$BAR->advance();

        $lareloq->setModels(
            function(
                DBTable $table){
                self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% {$table->name}: %message%");
                self::$BAR->advance();
                self::$BAR->setMessage("Writing Model...");
            },
            function(DBTable $table){
                self::$BAR->advance();
            }
        );
        $lareloq->setExtensions();
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Done!');
        self::$BAR->finish();
    }
}