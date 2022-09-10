<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBReferential;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\Polymorphic;
use Angujo\Lareloquent\Path;
use Angujo\Lareloquent\Enums\Referential;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

class DBConnection
{
    public string  $name     = 'default';
    public string $dbname   = 'mneapi';
    private string $dbms     = 'mysql';
    private string $host     = 'localhost';
    private string $username = 'root';
    private string $password = 'root';

    private $excludes     = [];
    private $only         = [];
    private $replacements = [];

    private EasyDB $DB;

    protected function __construct()
    {
    }

    private function getDb()
    {
        return $this->DB ??
            ($this->DB = Factory::fromArray(["{$this->dbms}:host={$this->host};dbname={$this->dbname}",
                                             $this->username, $this->password]));
    }

    private function prepareTables()
    {
        $this->replacements = ['{db}'       => $this->dbname,
                               '{andwhere}' => '',
                               '{tbl}'      => '',
                               '{pivots}'   => implode(', ', array_map(function($tbl){ return "'{$tbl}'"; }, LarEloquent::config()->pivot_tables??['[*UNKNOWN_TABLE_NAME*]']))];
        $this->excludes     = (!LarEloquent::config()->process_pivot_tables && isset(LarEloquent::config()->pivot_tables)) ? LarEloquent::config()->pivot_tables : [];
        if (isset(LarEloquent::config()->only_tables)) {
            $this->only = array_diff(LarEloquent::config()->only_tables, $this->excludes);
        } else {
            $this->excludes = [...$this->excludes, ...(isset(LarEloquent::config()->excluded_tables) ? LarEloquent::config()->excluded_tables : [])];
        }
        return $this;
    }

    private function replacedSql($file_name, $table_name = '', $tbl_alias = '', $ref_table = false)
    {
        $this->replacements['{tbl}'] = $table_name;
        $file_name                   = strtolower($file_name);
        if ($tbl_alias) $tbl_alias = "{$tbl_alias}.";
        $tbl_column = is_bool($ref_table) ? ($ref_table ? 'REFERENCED_TABLE_NAME' : 'TABLE_NAME') : $ref_table;
        if (!empty($this->only)) {
            $this->replacements['{andwhere}'] = " AND {$tbl_alias}{$tbl_column} IN (".(implode(', ', array_map(function($c){ return "'{$c}'"; }, $this->only))).')';
        } else {
            if (!empty($this->excludes)) {
                $this->replacements['{andwhere}'] = " AND {$tbl_alias}{$tbl_column} NOT IN (".(implode(', ', array_map(function($c){ return "'{$c}'"; }, $this->excludes))).')';
            }
        }
        return str_replace(array_keys($this->replacements), array_values($this->replacements),
                           file_get_contents(Path::Combine(BASE_DIR, 'scripts', $this->dbms, "{$file_name}.sql")));
    }

    public function countTables()
    {
        return (int)$this->getDb()->single('SELECT count(1) FROM ('.$this->replacedSql('tables', tbl_alias: 't').') AS t');
    }

    public function Tables()
    : \Generator
    {
        $rows = $this->getDb()->run($this->replacedSql(__FUNCTION__, tbl_alias: 't'));
        foreach ($rows as $row) {
            $tbl = new DBTable();
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($tbl, strtolower($array_key))) continue;
                $tbl->{strtolower($array_key)} = $row[$array_key];
            }
            yield $tbl;
        }
    }

    public function polymorphism()
    : \Generator
    {
        $rows = $this->getDb()->run($this->replacedSql(__FUNCTION__));
        foreach ($rows as $row) {
            $polymorphic = new Polymorphic();
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($polymorphic, strtolower($array_key))) continue;
                $polymorphic->{strtolower($array_key)} = $row[$array_key];
            }
            yield $polymorphic;
        }
    }

    public function Columns(string $tbl_name)
    : \Generator
    {
        if (!LarEloquent::validTable($tbl_name)) return;
        $rows = $this->getDb()->run($this->replacedSql(__FUNCTION__, $tbl_name, 'c'));
        foreach ($rows as $row) {
            $col = new DBColumn();
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($col, strtolower($array_key))) continue;
                $col->{strtolower($array_key)} = $row[$array_key];
            }
            LarEloquent::addUsage($tbl_name, ...$col->GetUses());
            yield $col;
        }
    }

    /**
     * @param string $tbl_name
     *
     * @return \Generator|Referential[]
     */
    public function one2One(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::ONE2ONE);
    }

    public function BelongsTo(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::BELONGSTO);
    }

    public function belongsToMany(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::BELONGSTOMANY);
    }

    public function One2Many(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::ONE2MANY);
    }

    public function oneThrough(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::ONETHROUGH);
    }

    public function manyThrough(string $tbl_name)
    : \Generator
    {
        return $this->Referential($tbl_name, __FUNCTION__, Referential::MANYTHROUGH);
    }

    private function Referential(string $tbl_name, string $file_name, Referential $referential)
    : \Generator
    {
        if (!LarEloquent::validTable($tbl_name)) return;
        $rows = $this->getDb()->run($this->replacedSql($file_name, $tbl_name));
        foreach ($rows as $row) {
            $ref = new DBReferential($referential);
            foreach (array_keys($row) as $array_key) {
                if (!property_exists($ref, strtolower($array_key))) continue;
                $ref->{strtolower($array_key)} = $row[$array_key];
            }
            yield $ref;
        }
    }

    public static function fromDefault()
    {
        return (new DBConnection())->prepareTables();
    }

    public static function fromConfig()
    {
        $m           = (new DBConnection());
        $m->name     = LarEloquent::config()->command['name'];
        $m->dbms     = LarEloquent::config()->command['dbms'];
        $m->host     = LarEloquent::config()->command['host'];
        $m->dbname   = LarEloquent::config()->command['dbname'];
        $m->username = LarEloquent::config()->command['username'];
        $m->password = LarEloquent::config()->command['password'];
        return $m->prepareTables();
    }
}