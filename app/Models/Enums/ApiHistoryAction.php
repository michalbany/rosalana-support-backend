<?php

namespace App\Models\Enums;

enum ApiHistoryAction:string
{
    case ASSIGNED = 'ASSIGNED';
    case UNASSIGNED = 'UNASSIGNED';
    case UPDATED = 'UPDATED';
    case NO_CHANGE = 'NO_CHANGE';
}
