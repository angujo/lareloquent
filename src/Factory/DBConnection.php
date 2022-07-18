<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Path;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

class DBConnection
{
    public string  $name     = 'default';
    private string $dbms     = 'mysql';
    private string $host     = 'localhost';
    private string $dbname   = 'sakila';
    private string $username = 'root';
    private string $password = 'root';

    private EasyDB $DB;

    protected function __construct(){ }

    private function getDb()
    {
        return $this->DB ??
            ($this->DB = Factory::fromArray(["{$this->dbms}:host={$this->host};dbname={$this->dbname}",
                                             $this->username, $this->password]));
    }

    public function Tables()
    : \Generator
    {
        $rows = $this->getDb()->run($sql = str_replace('{db}', $this->dbname, file_get_contents(Path::Combine(BASE_DIR, 'scripts', 'mysql', 'tables.sql'))));
        echo $sql;
        foreach ($rows as $row) {
            $tbl = new DBTable();
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($tbl, strtolower($array_key))) continue;
                $tbl->{strtolower($array_key)} = $row[$array_key];
            }
            yield $tbl;
        }
    }

    public function Columns(string $tbl_name)
    : \Generator
    {
        $rows = $this->getDb()->run(str_replace('{db}', $this->dbname,
                                                str_replace('{tbl}', $tbl_name,
                                                            file_get_contents(Path::Combine(BASE_DIR, 'scripts', 'mysql', 'columns.sql')))));
        foreach ($rows as $row) {
            $col = new DBColumn();
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($col, strtolower($array_key))) continue;
                $col->{strtolower($array_key)} = $row[$array_key];
            }
            yield $col;
        }
    }

    public static function fromDefault()
    {
        return new DBConnection();
    }
}