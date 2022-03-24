<?php

namespace Eelcol\LaravelMeilisearch\Enums;

enum QueryWhereBoolean:string
{
    case And = "AND";
    case Or = "OR";
}