<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Models\Polymorphic;
use function Angujo\Lareloquent\flatten_array;

class Morpher
{
    private static array $mes = [];

    private DBConnection $connection;
    /** @var array|Polymorphic[] */
    private $morphs = [];

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
        }
    }

    private static function instance(DBConnection $connection)
    : Morpher
    {
        return self::$mes[$connection->name] ?? (self::$mes[$connection->name] = new Morpher($connection));
    }

    protected function isMorpher(string $tbl_name)
    {
        return array_key_exists($tbl_name, $this->morphs);
    }

    /**
     * @param string $tbl_name
     *
     * @return Polymorphic[]|array
     */
    protected function getMorphers(string $tbl_name)
    {
        return $this->morphs[$tbl_name] ?? [];
    }

    /**
     * @param string $tbl_name
     *
     * @return array|Polymorphic[]
     */
    protected function getMorphs(string $tbl_name)
    {
        return flatten_array(array_map(function($m) use ($tbl_name){
            return array_filter(array_map(function(Polymorphic $polymorphic) use ($tbl_name){ return $polymorphic->isReferenced($tbl_name) ? $polymorphic : null; }, $m));
        }, $this->morphs));
    }

    /**
     * @param DBConnection $connection
     * @param string       $table_name
     *
     * @return Polymorphic[]|array
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