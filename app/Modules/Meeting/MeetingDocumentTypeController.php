<?php

namespace App\Modules\Meeting;

use App\Modules\Meeting\Controllers\BaseMeetingCatalogController;
use App\Modules\Meeting\Models\MeetingDocumentType;

class MeetingDocumentTypeController extends BaseMeetingCatalogController
{
    protected function modelClass(): string
    {
        return MeetingDocumentType::class;
    }

    protected function successLabel(): string
    {
        return 'loại tài liệu họp';
    }

    protected function fileName(): string
    {
        return 'meeting-document-types.xlsx';
    }
}
