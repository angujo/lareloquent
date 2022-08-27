<?php

namespace Angujo\Lareloquent\Enums;

enum SQLType: string
{
    case TINYINT = 'tinyint';
    case INT = 'int';
    case MEDIUMINT = 'mediumint';
    case BIGINT = 'bigint';
    case SMALLINT = 'smallint';
    case YEAR = 'year';
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case DECIMAL = 'decimal';
    case DATETIME = 'datetime';
    case TIMESTAMP = 'timestamp';
    case ENUM = 'enum';
    case JSON = 'json';
    case TEXT = 'text';
    case MEDIUMTEXT = 'mediumtext';
    case SET = 'set';
    case CHAR = 'char';
    case BINARY = 'binary';
    case VARBINARY = 'varbinary';
    case BLOB = 'blob';
    case MEDIUMBLOB = 'mediumblob';
    case TIME = 'time';
    case LONGBLOB = 'longblob';
    case DATE = 'date';
    case GEOMETRY = 'geometry';
    case VARCHAR = 'varchar';
    case LONGTEXT = 'longtext';
}