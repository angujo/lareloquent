<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBEnum;
use Exception;

class Enumer
{
    /**
     * @var array<string,self>
     */
    private static array $mes = [];

    private DBConnection $connection;
    /** @var DBEnum[]|array<string,DBEnum> */
    private array $enums = [];

    protected function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
        $this->load();
    }

    private function load()
    {
        foreach ($this->connection->enums() as $DBEnum) {
            if (isset($this->enums[$DBEnum->column_name])) {
                $this->enums[$DBEnum->column_name]->merge($DBEnum->cases());
            } else  $this->enums[$DBEnum->column_name] = $DBEnum;
        }
    }

    private function _getEnum(string $name)
    {
        return $this->enums[$name] ?? null;
    }

    /**
     * @param string $con_name
     * @param string $name
     *
     * @return DBEnum|null
     * @throws Exception
     */
    public static function getEnum(string $con_name, string $name)
    {
        if (empty(self::$mes[$con_name])) throw new Exception('Enums need to be loaded first!');
        return self::$mes[$con_name]->_getEnum($name);
    }

    /**
     * @param DBConnection $connection
     *
     * @return DBEnum[]|array<string, DBEnum>
     */
    public static function getEnums(DBConnection $connection)
    {
        return self::instance($connection)->enums;
    }

    private static function instance(DBConnection $connection)
    : Enumer
    {
        return self::$mes[$connection->name] ?? (self::$mes[$connection->name] = new Enumer($connection));
    }

    public static function Write(DBConnection $connection)
    {
        foreach (self::getEnums($connection) as $enum) {
            ColumnEnum::Write($enum);
            if (LarEloquent::config()->typescript) TypeScriptEnum::Write($enum);
        }
    }
}
