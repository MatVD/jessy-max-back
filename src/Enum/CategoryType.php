<?php

namespace App\Enum;

enum CategoryType: string
{
    case EVENT = 'event';
    case FORMATION = 'formation';
    case BOTH = 'both';
}