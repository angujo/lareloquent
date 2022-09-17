<?php

namespace Angujo\Lareloquent\Enums;

use Angujo\Lareloquent\Traits\BaseEnumTrait;
use function Angujo\Lareloquent\str_equal;

enum DataType: string
{
    use BaseEnumTrait;

    /** For all types that can be considered as String in PhP */
    case STRING = 'string';
    /** For all types that can be considered as Boolean in PhP */
    case BOOL = 'bool';
    /** For all types that can be considered as Integer in PhP */
    case INT = 'int';
    /** For all types that can be considered as Float in PhP */
    case FLOAT = 'float';
    /** For all types that can be considered as Datetime in PhP */
    case DATETIME = 'datetime';
    /** For all types that can be considered as Json in PhP */
    case JSON = 'json';
    /** For all types that can be considered as Array in PhP */
    case ARRAY = 'array';
    /** For all types that can be considered as Image in PhP */
    case IMAGE = 'image';
    /** For all types that can be considered as File in PhP */
    case FILE = 'file';
    /** For all types that can be considered as IP Address in PhP */
    case IP = 'ip';
    /** For all types that can be considered as IP Address in PhP */
    case UUID = 'uuid';
    /** For all types that can be considered as IP Address in PhP */
    case URL = 'url';
    /** For all types that can be considered as IP Address in PhP */
    case EMAIL = 'email';
    /** For all types that can be considered as IP Address in PhP */
    case MAC_ADDRESS = 'mac_address';
    /* To flag as instantiated */
    case NONE = 'none';
}