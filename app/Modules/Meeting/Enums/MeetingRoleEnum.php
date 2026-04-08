<?php

namespace App\Modules\Meeting\Enums;

enum MeetingRoleEnum: string
{
    case Chair = 'chair';
    case Secretary = 'secretary';
    case Delegate = 'delegate';
    case Guest = 'guest';
}
