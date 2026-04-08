<?php

namespace App\Modules\Meeting\Enums;

enum SpeechRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
