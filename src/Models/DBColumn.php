<?php

namespace Angujo\Lareloquent\Models;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\Enums\TSType;
use Angujo\Lareloquent\Factory\BaseRequest;
use Angujo\Lareloquent\Factory\Enumer;
use Angujo\Lareloquent\Factory\ValueCast;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Enums\SQLType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;

class DBColumn extends DBInterface
{
    public string      $table_name;
    public string      $column_name;
    public string|null $referenced_table_name;
    public string|null $column_type;
    public string|null $referenced_column_name;
    public string      $column_comment;
    public int|null    $ordinal_position;
    public int|null    $character_maximum_length = null;
    public int|null    $numeric_scale            = null;
    public string|null $column_default;
    public string      $data_type;
    public bool        $is_nullable;
    public bool        $is_primary;
    public bool        $is_unique;
    public bool        $increments;
    public bool        $is_updating;

    private ValueCast|null $valueCast   = null;
    private ?DataType      $id_type;
    private array          $_validation = [];

    public function getValidation()
    : array
    {
        if (empty($this->column_comment)) return [];
        if (!empty($this->_validation)) return $this->_validation;
        $raw_valids = explode(';', preg_replace('/^(.*?)validation(\s+)?:(\s+)?\{(.*?)\}(.*?)$/', '$4', $this->column_comment));

        $raw_valids = array_values(array_map(function($v){ return array_map('trim', explode(':', $v)); }, $raw_valids));
        $raw_valids = array_combine(array_column($raw_valids, 0), array_map(function($v){
            array_shift($v);
            return implode('', $v);
        }, $raw_valids));
        return $this->_validation = array_filter($raw_valids, function($k){ return array_key_exists(strtolower($k), BaseRequest::$default_messages); }, ARRAY_FILTER_USE_KEY);
    }

    public function cast()
    {
        return ($this->valueCast ?? ($this->valueCast = new ValueCast($this)))->_getCast();
    }

    public function isEnum()
    {
        return str_equal($this->data_type, SQLType::ENUM->value);
    }

    /**
     * @throws \Exception
     */
    public function getEnum()
    : ?DBEnum
    {
        return Enumer::getEnum($this->connection_name, $this->column_name);
    }

    public function constantName()
    {
        return strtoupper(LarEloquent::config()->constant_column_prefix.preg_replace(['/(^[^a-zA-Z]+)|([^a-zA-Z\d]+$)/', '/[^a-zA-Z0-9]+/'], ['', '_'], $this->column_name));
    }

    public function constantProperty()
    {
        return (new PropertyGenerator($this->constantName(), new PropertyValueGenerator($this->column_name), PropertyGenerator::FLAG_CONSTANT | AbstractMemberGenerator::FLAG_FINAL));
    }

    public function docPropertyTag()
    {
        if (!empty($this->cast()) && class_exists($this->cast())) {
            $types = [$this->cast()];
        } else $types = [$this->isEnum() ? $this->getEnum()->getName() : $this->dataType()];
        if ($this->is_nullable) $types[] = 'null';
        return (new PropertyTag($this->column_name))
            ->setTypes($types)
            ->setDescription($this->column_comment);
    }

    public function isParentColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->parent_columns);
    }

    public function isCreatedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->create_columns) && $this->isDateTime();
    }

    public function isUpdatedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->update_columns) && $this->isDateTime();
    }

    public function isDeletedColumn()
    {
        return in_array($this->column_name, LarEloquent::config()->soft_delete_columns) && $this->isDateTime();
    }

    public function isDateTime()
    {
        return in_array($this->data_type, ['date_time', 'timestamp', 'datetime']);
    }

    public function docType()
    {
        return $this->dataType().($this->is_nullable ? '|null' : '');
    }

    public function defaultValue()
    {
        if (is_null($this->column_default)) return null;
        return match ($this->PhpDataType()) {
            DataType::DATETIME => str_contains(strtoupper($this->column_default), 'CURRENT_TIMESTAMP') ? null : $this->column_default,
            DataType::BOOL => (bool)$this->column_default,
            DataType::INT => intval($this->column_default),
            DataType::FLOAT => floatval($this->column_default),
            default => $this->column_default,
        };
    }

    public function dataType()
    {
        return (str_equal(DataType::DATETIME->value, $this->PhpDataType()->value) ? basename(Carbon::class) : $this->PhpDataType()->value);
    }

    public function PhpDataType()
    : DataType
    {
        switch ($this->data_type) {
            case SQLType::TINYINT->value:
                return DataType::BOOL;
            case SQLType::INT->value:
            case SQLType::MEDIUMINT->value:
            case SQLType::BIGINT->value:
            case SQLType::SMALLINT->value:
            case SQLType::YEAR->value:
                return DataType::INT;
            case SQLType::FLOAT->value:
            case SQLType::DOUBLE->value:
            case SQLType::DECIMAL->value:
                return DataType::FLOAT;
            case SQLType::DATETIME->value:
            case SQLType::TIMESTAMP->value:
                return DataType::DATETIME;
            case SQLType::JSON->value:
                return DataType::JSON;
            case SQLType::SET->value:
                return DataType::ARRAY;
            case SQLType::ENUM->value:
            case SQLType::TEXT->value:
            case SQLType::MEDIUMTEXT->value:
            case SQLType::CHAR->value:
            case SQLType::BINARY->value:
            case SQLType::VARBINARY->value:
            case SQLType::BLOB->value:
            case SQLType::MEDIUMBLOB->value:
            case SQLType::TIME->value:
            case SQLType::LONGBLOB->value:
            case SQLType::DATE->value:
            case SQLType::GEOMETRY->value:
            case SQLType::VARCHAR->value:
            case SQLType::LONGTEXT->value:
            default:
                return DataType::STRING;
        }
    }

    public function tsDataType()
    : TSType
    {
        return match ($this->data_type) {
            SQLType::TINYINT->value, SQLType::BOOL->value, SQLType::BOOLEAN->value => TSType::BOOLEAN,
            SQLType::INT->value, SQLType::MEDIUMINT->value, SQLType::BIGINT->value, SQLType::SMALLINT->value, SQLType::YEAR->value, SQLType::FLOAT->value, SQLType::DOUBLE->value, SQLType::DECIMAL->value => TSType::NUMBER,
            SQLType::JSON->value, SQLType::SET->value => TSType::ARRAY,
            SQLType::TEXT->value, SQLType::MEDIUMTEXT->value, SQLType::CHAR->value, SQLType::VARCHAR->value, SQLType::LONGTEXT->value => TSType::STRING,
            SQLType::ENUM->value => TSType::ENUM,
            SQLType::DATETIME->value, SQLType::TIMESTAMP->value, SQLType::DATE->value => TSType::DATE,
            default => TSType::ANY,
        };
    }

    public function tsTypeValue()
    : string
    {
        return match ($this->tsDataType()) {
            TSType::TUPLE, TSType::ARRAY => 'Array<any>',
            TSType::ENUM => $this->getEnum()->case(),
            default => $this->tsDataType()->value,
        };
    }

    public function tsPropertyName()
    {
        return $this->column_name.($this->is_nullable || $this->increments ? '?' : '');
    }

    public function tsValue()
    {
        if ($this->increments || $this->is_nullable || ($this->isEnum() && $this->getEnum()->is_nullable)) return null;
        $def = $this->defaultValue();
        if (!is_null($def)) return var_export($def, true);
        return match ($this->tsDataType()) {
            TSType::STRING => "''",
            TSType::NUMBER => '0',
            TSType::TUPLE, TSType::ARRAY => '[]',
            TSType::BOOLEAN => 'false',
            TSType::ENUM => $this->getEnum()->case(),
            TSType::DATE => 'new Date',
            default => null,
        };
    }

    public function isMacAddress()
    : bool
    {
        return DataType::MAC_ADDRESS === $this->getTypeId();
    }

    public function isUUID()
    : bool
    {
        return DataType::UUID === $this->getTypeId();
    }

    public function isURL()
    : bool
    {
        return DataType::URL === $this->getTypeId();
    }

    public function isEmail()
    : bool
    {
        return DataType::EMAIL === $this->getTypeId();
    }

    public function isIpAddress()
    : bool
    {
        return DataType::IP === $this->getTypeId();
    }

    public function isArray()
    : bool
    {
        return in_array(DataType::ARRAY, [$this->getTypeId(), $this->PhpDataType()]);
    }

    public function isImage()
    : bool
    {
        return DataType::IMAGE === $this->getTypeId();
    }

    public function isFile()
    : bool
    {
        return DataType::FILE === $this->getTypeId();
    }

    public function isJson()
    : bool
    {
        return in_array(DataType::JSON, [$this->getTypeId(), $this->PhpDataType()]);
    }

    protected function getTypeId()
    : DataType
    {
        if (isset($this->id_type)) return $this->id_type;
        $identifiers = is_array(LarEloquent::config()->identified_columns) ? LarEloquent::config()->identified_columns : [];
        $permitted   = [DataType::ARRAY->value, DataType::IMAGE->value, DataType::FILE->value, DataType::JSON->value, DataType::IP->value,
                        DataType::UUID->value, DataType::URL->value, DataType::EMAIL->value, DataType::MAC_ADDRESS->value,];
        foreach ($identifiers as $key => $identities) {
            if (!(is_array($identities) && in_array($key, $permitted))) continue;
            foreach ($identities as $identity) {
                if ($this->idTypeMatches($identity)) return $this->id_type = (DataType::tryFrom($key) ?? DataType::NONE);
            }
        }
        return $this->id_type = DataType::NONE;
    }

    private function idTypeMatches($matches)
    {
        if (!is_array($matches)) return false;
        $matches = array_intersect_key($matches, array_flip(['name', 'type']));
        return count($matches) > 0 && (!array_key_exists('name', $matches) || $this->nameMatches($matches['name'])) &&
            (!array_key_exists('type', $matches) || $this->typeMatches($matches['type']));
    }

    private function nameMatches($match)
    {
        if (!is_string($match)) return false;
        $match = preg_replace('/%/', '(.*?)', $match);
        return 1 === preg_match("/$match/", $this->column_name);
    }

    private function typeMatches($match)
    {
        if (!is_string($match)) return false;
        return str_equal($match, $this->data_type) || (str_equal('string', $match) && str_equal($this->data_type, SQLType::VARCHAR->value));
    }

    public function maxValue()
    {
        if (!in_array($this->PhpDataType(), [DataType::INT, DataType::STRING, DataType::FLOAT])) return null;
        return DataType::STRING == $this->PhpDataType() ?
            $this->character_maximum_length :
            floatval("1".implode('', array_map(function($v){ return "0"; }, range(1, $this->character_maximum_length - $this->numeric_scale)))) - 1;
    }
}