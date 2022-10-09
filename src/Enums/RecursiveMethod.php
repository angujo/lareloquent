<?php

namespace Angujo\Lareloquent\Enums;

use function Angujo\Lareloquent\snake_case;

enum RecursiveMethod: string
{
    case ANCESTORS = 'ancestors';
    case ANCESTORS_SELF = 'ancestorsAndSelf';
    case BLOODLINE = 'bloodline';
    case CHILDREN = 'children';
    case CHILDREN_SELF = 'childrenAndSelf';
    case DESCENDANTS = 'descendants';
    case DESCENDANTS_SELF = 'descendantsAndSelf';
    case PARENT = 'parent';
    case PARENT_SELF = 'parentAndSelf';
    case ROOT_ANCESTOR = 'rootAncestor';
    case SIBLINGS = 'siblings';
    case SIBLINGS_SELF = 'siblingsAndSelf';

    public function description()
    : string
    {
        return match ($this) {
            self::ANCESTORS => 'The model\'s recursive parents.',
            self::ANCESTORS_SELF => 'The model\'s recursive parents and itself.',
            self::BLOODLINE => 'The model\'s ancestors, descendants and itself.',
            self::CHILDREN => 'The model\'s direct children.',
            self::CHILDREN_SELF => 'The model\'s direct children and itself.',
            self::DESCENDANTS => 'The model\'s recursive children.',
            self::DESCENDANTS_SELF => 'The model\'s recursive children and itself.',
            self::PARENT => 'The model\'s direct parent.',
            self::PARENT_SELF => 'The model\'s direct parent and itself.',
            self::ROOT_ANCESTOR => 'The model\'s topmost parent.',
            self::SIBLINGS => 'The parent\'s other children.',
            self::SIBLINGS_SELF => 'All the parent\'s children.',
        };
    }

    public function isCollection()
    : bool
    {
        return match ($this) {
            RecursiveMethod::PARENT, RecursiveMethod::ROOT_ANCESTOR => false,
            default => true,
        };
    }

    public function tsPropertyName()
    : string
    {
        return snake_case($this->value).'?';
    }

    public function tsTypeValue(string $model_name)
    : string
    {
        return $this->isCollection() ? implode('', ['Array<', $model_name, '>']) : $model_name;
    }
}
