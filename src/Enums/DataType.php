<?php

namespace Angujo\Lareloquent\Enums;

enum DataType: string
{
    case STRING = 'string';
    case BOOL = 'bool';
    case INT = 'int';
    case FLOAT = 'float';
    case DATETIME = 'datetime';
}