<?php

namespace Angujo\Lareloquent\Enums;

enum Referential
{
    case ONE2ONE;
    case BELONGS_TO;
    case BELONGS_TO_MANY;
    case ONE2MANY;
    case ONE_THROUGH;
    case MANY_THROUGH;
}