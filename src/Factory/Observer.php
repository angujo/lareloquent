<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\clean_template;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class Observer
{
    private              $events = ['retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted',
                                    'trashed', 'forceDeleted', 'restoring', 'restored', 'replicating',];
    private DBTable      $table;
    private string       $table_namespace;
    private string       $table_name;
    private string       $func_name;
    private string|false $template;
    private string       $name;

    public function __construct(DBTable $table)
    {
        $this->table           = $table;
        $this->template        = file_get_contents(Path::Template('observer.tpl'));
        $this->table_namespace = implode('\\', [LarEloquent::config()->namespace, $this->table_name = model_name($this->table->name)]);
        $this->name            = model_name($this->table_name.'_'.LarEloquent::config()->observer_suffix);
        $this->func_name       = method_name($table->name);
    }

    private function parseTemplate()
    {
        $this->template = str_replace(
            ['{namespace}', '{table_namespace}', '{name}', '{events}'],
            [LarEloquent::config()->observer_namespace, $this->table_namespace, $this->name, $this->runEvents()],
            $this->template);
        return $this;
    }

    private function runEvents()
    {
        return implode("\n\t\n", array_map(function($evt){ return $this->event($evt); }, $this->events));
    }

    private function event(string $event)
    {
        return "\t/**".
            "\n\t* Handle the {$this->table_name} '{$event}' event.".
            "\n\t*".
            "\n\t* @param  {$this->table_name}  \${$this->func_name}".
            "\n\t* @return void".
            "\n\t*/".
            "\n\tpublic function {$event}({$this->table_name} \${$this->func_name})".
            "\n\t{".
            "\n\t\t//TODO Add {$event} event actions here".
            "\n\t}";
    }

    public function __toString()
    : string
    {
        $this->parseTemplate();
        return clean_template($this->template);
    }

    private function _write(string $path = null)
    : void
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->observers_dir, model_file($this->name));
        if (file_exists($path)) return;
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
    }

    public static function Write(DBTable $table)
    : void
    {
        $obs = new self($table);
        $obs->_write();
        ProviderBoot::addObserver($obs->table_namespace, implode('\\', [LarEloquent::config()->observer_namespace, $obs->name]));
    }
}