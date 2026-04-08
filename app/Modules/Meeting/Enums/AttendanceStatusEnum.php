<?php

namespace App\Modules\Meeting\Enums;

enum AttendanceStatusEnum: string
{
    case Pending = 'pending';
    case Present = 'present';
    case Absent = 'absent';
    case Delegated = 'delegated';
}
