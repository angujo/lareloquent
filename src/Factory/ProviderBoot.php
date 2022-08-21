<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class ProviderBoot
{
    private static array          $morphs    = [];
    private static array          $observers = [];
    private static string         $file_name = 'ServiceProviderBoot';
    private static ClassGenerator $class;

    public static function addObserver($tbl_class, $obs_class)
    {
        self::getClass()->addUse($tbl_class)->addUse($obs_class);
        self::$observers[] = basename($tbl_class)."::observe(".basename($obs_class)."::class);";
    }

    /**
     * @param array|string|string[] $tbl_name
     *
     * @return void
     */
    public static function addMorph()
    : void
    {
        $args = func_get_args();
        foreach ($args as $tbl_name) {
            if (is_array($tbl_name)) {
                foreach ($tbl_name as $item) {
                    self::addMorph($item);
                }
                continue;
            }
            self::$morphs[$tbl_name] = implode('\\', [LarEloquent::config()->namespace, model_name($tbl_name)]);
        }
    }

    private static function morphMethod()
    {
        self::getClass()->addUse(Relation::class);
        return (new MethodGenerator('relationsMorph'))
            ->setStatic(true)
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Method to be called to enforce Relation Morphs')
                              ->setTag(GeneralTag::fromContent('see', 'https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types')))
            ->setBody('Relation::enforceMorphMap('.(new ValueGenerator(self::$morphs, ValueGenerator::TYPE_ARRAY_SHORT)).');');
    }

    private static function observerMethod()
    {
        return (new MethodGenerator('modelObservers'))
            ->setStatic(true)
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Method to be called to register observers and events')
                              ->setTag(GeneralTag::fromContent('see', 'https://laravel.com/docs/eloquent#defining-observers')))
            ->setBody(implode("\n", self::$observers));
    }

    private static function registerAll()
    {
        return (new MethodGenerator('boot'))
            ->setStatic(true)
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Method to be called to register all boot elements')
                              ->setLongDescription("See documentation for the called methods\nto see how to use.")
            )
            ->setBody(implode("\n", ['self::modelObservers();', 'self::relationsMorph();']));
    }

    public static function Write()
    {
        $class = self::getClass()
                     ->addMethodFromGenerator(self::morphMethod())
                     ->addMethodFromGenerator(self::observerMethod())
                     ->addMethodFromGenerator(self::registerAll());
        $file  = (new FileGenerator())
            ->setClass($class)
            ->generate();
        $path  = $path ?? Path::Combine(LarEloquent::config()->base_dir, model_name(LarEloquent::config()->eloquent_extension_dir), model_file(self::$file_name));
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $file);
    }

    private static function getClass()
    {
        return self::$class ?? (self::$class = (new ClassGenerator(self::$file_name))
            ->setNamespaceName(implode('\\', [LarEloquent::config()->namespace, model_name(LarEloquent::config()->eloquent_extension_dir)]))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Class to provide methods for the boot')));
    }
}