<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Models\Polymorphic;
use function Angujo\Lareloquent\flatten_array;

class Morpher
{
    private static $mes = [];

    private DBConnection $connection;
    private              $ran = false;
    /** @var array|Polymorphic[] */
    private $morphs = [];

    // private $morph_tables = [];

    protected function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
        $this->load();
    }

    private function load()
    {
        foreach ($this->connection->polymorphism() as $polymorphic) {
            if (!isset($this->morphs[$polymorphic->table_name])) {
                $this->morphs[$polymorphic->table_name]       = [];
                $this->morph_tables[$polymorphic->table_name] = [];
            }
            $this->morphs[$polymorphic->table_name][] = $polymorphic;
            // $this->morph_tables[$polymorphic->table_name] = array_unique([...$this->morph_tables[$polymorphic->table_name], ...$polymorphic->referencedTables()]);
        }
    }

    protected function isMorpher(string $tbl_name)
    {
        return array_key_exists($tbl_name, $this->morphs);
    }

    protected function getMorphers(string $tbl_name)
    {
        return $this->morphs[$tbl_name] ?? [];
    }

    protected function getMorphs(string $tbl_name)
    {
        return flatten_array(array_map(function($m) use ($tbl_name){
            return array_filter(array_map(function(Polymorphic $polymorphic) use ($tbl_name){ return $polymorphic->isReferenced($tbl_name) ? $polymorphic : null; }, $m));
        }, $this->morphs));
    }

    private static function instance(DBConnection $connection)
    : Morpher
    {
        return self::$mes[$connection->name] ?? (self::$mes[$connection->name] = new Morpher($connection));
    }

    /**
     * @param DBConnection $connection
     * @param string       $table_name
     *
     * @return Polymorphic|array|mixed
     */
    public static function morphers(DBConnection $connection, string $table_name)
    {
        return self::instance($connection)->getMorphers($table_name);
    }

    /**
     * @param DBConnection $connection
     * @param string       $table_name
     *
     * @return Polymorphic[]
     */
    public static function morphs(DBConnection $connection, string $table_name)
    {
        return self::instance($connection)->getMorphs($table_name);
    }
}