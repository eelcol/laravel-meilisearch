<?php

namespace Eelcol\LaravelMeilisearch\Enums;

enum QueryOrderBy:string
{
    case Asc = "asc";
    case Desc = "desc";
}