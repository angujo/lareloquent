<?php
/**
 * @author       bangujo ON 2021-04-17 09:10
 * @project      lareloquent
 * @ide          PhpStorm
 * @originalFile LarEloquentServiceProvider.php
 */

namespace Angujo\Lareloquent\Laravel;


use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Path;
use Angujo\Lareloquent\Laravel\Factory as LarEloqFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Class LarEloquentServiceProvider
 *
 * @package Angujo\LarEloquent\Laravel
 */
class LarEloquentServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register(); // TODO: Change the autogenerated stub
        $this->app->singleton(LarEloqFactory::class, function(){
            return new LarEloqFactory();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ModelCommand::class]);
        }
        $this->publishes([Path::Combine(Path::$BASE, 'config.php') => config_path(LarEloquent::LM_APP_NAME.'.php')]);
    }
}