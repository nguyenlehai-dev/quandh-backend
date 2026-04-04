<?php

namespace App\Modules\Meeting;

use App\Modules\Meeting\Controllers\BaseMeetingCatalogController;
use App\Modules\Meeting\Models\MeetingType;

class MeetingTypeController extends BaseMeetingCatalogController
{
    protected function modelClass(): string
    {
        return MeetingType::class;
    }

    protected function successLabel(): string
    {
        return 'loại cuộc họp';
    }

    protected function fileName(): string
    {
        return 'meeting-types.xlsx';
    }
}
