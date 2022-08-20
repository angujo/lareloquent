<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\DocProperty;
use Angujo\Lareloquent\Models\HasUsage;
use Angujo\Lareloquent\Path;
use function Angujo\Lareloquent\clean_template;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;

class WorkModel
{
    use HasUsage;

    private DBTable $table;
    private string  $name;
    private string  $parent_class;
    private string  $template;
    private bool    $full_process;

    /** @var array|DBColumn[] */
    private $columns = [];
    /** @var DocProperty[] */
    private array $doc_properties = [];
    private array $col_constants  = [];

    protected function __construct(DBTable $table)
    {
        $this->table        = $table;
        $this->parent_class = implode('\\', [LarEloquent::config()->namespace, model_name(LarEloquent::config()->base_abstract_prefix), model_name(LarEloquent::config()->base_abstract_prefix.'_'.$table->name)]);
        LarEloquent::addUsage($table->name, $this->parent_class);
        $this->name     = model_name($this->table->name);
        $this->template = file_get_contents(Path::Template('work-model.tpl'));
    }

    private function name()
    {
        $this->template = str_replace('{name}', $this->name, $this->template);
        return $this;
    }

    private function namespace()
    {
        $this->template = str_replace('{namespace}', LarEloquent::config()->namespace, $this->template);
        return $this;
    }

    private function _uses()
    {
        $this->template = str_replace('{uses}', implode("\n", array_map(function(string $use){ return "use {$use};"; }, [$this->parent_class])), $this->template);
        return $this;
    }

    private function parent()
    {
        $this->template = preg_replace('/\s+(extends)\s+\{parent}/', " $1 ".basename($this->parent_class), $this->template);
        return $this;
    }

    private function clean()
    {
        $this->template = clean_template( $this->template);
        return $this;
    }

    private function prepare()
    {
        return $this->name()->namespace()->parent();
    }

    private function _convert()
    {
        return $this->prepare()->_uses()->clean();
    }

    public function __toString()
    : string
    {
        $this->_convert();
        return $this->template;
    }

    private function _write(string $path = null)
    : void
    {
        $path = $path ?? Path::Combine(LarEloquent::config()->base_dir, model_file($this->table->name));
        if (file_exists($path) && !LarEloquent::config()->overwrite) return;
        if (!file_exists($dir = dirname($path))) mkdir($dir, 0755, true);
        file_put_contents($path, $this.'');
    }

    public static function Write(DBTable $table)
    : void
    {
        (new WorkModel($table))->_write();
    }
}
