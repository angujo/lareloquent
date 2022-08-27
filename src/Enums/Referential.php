<?php

namespace Angujo\Lareloquent\Enums;

enum Referential
{
    case ONE2ONE;
    case BELONGSTO;
    case BELONGSTOMANY;
    case ONE2MANY;
    case ONETHROUGH;
    case MANYTHROUGH;
}