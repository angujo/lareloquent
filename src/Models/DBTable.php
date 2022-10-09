<?php

namespace Angujo\Lareloquent\Models;

class DBTable extends DBInterface
{
    public string $name;
    public string $type;
    public string $comment;
    public bool   $is_view;
}