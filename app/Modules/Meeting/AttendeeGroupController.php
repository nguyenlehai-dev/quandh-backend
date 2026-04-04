<?php

namespace App\Modules\Meeting;

use App\Modules\Meeting\Controllers\BaseMeetingCatalogController;
use App\Modules\Meeting\Models\AttendeeGroup;

class AttendeeGroupController extends BaseMeetingCatalogController
{
    protected function modelClass(): string
    {
        return AttendeeGroup::class;
    }

    protected function successLabel(): string
    {
        return 'nhóm thành phần';
    }

    protected function fileName(): string
    {
        return 'attendee-groups.xlsx';
    }
}
