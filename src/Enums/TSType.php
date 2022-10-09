<?php

namespace Angujo\Lareloquent\Enums;

/**
 * Data types for Typescript classes
 */
enum TSType: string
{
    case STRING = 'string';
    case NUMBER = 'number';
    case ARRAY = 'array';
    case BOOLEAN = 'boolean';
    case TUPLE = 'tuple';
    case ENUM = 'enum';
    case ANY = 'any';
    case VOID = 'void';
    case NEVER = 'never';
    case DATE = 'Date';
}