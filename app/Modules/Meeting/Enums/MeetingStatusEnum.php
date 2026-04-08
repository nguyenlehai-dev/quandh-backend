<?php

namespace App\Modules\Meeting\Enums;

enum MeetingStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
