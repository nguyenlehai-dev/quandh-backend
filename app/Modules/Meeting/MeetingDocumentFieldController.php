<?php

namespace App\Modules\Meeting;

use App\Modules\Meeting\Controllers\BaseMeetingCatalogController;
use App\Modules\Meeting\Models\MeetingDocumentField;

class MeetingDocumentFieldController extends BaseMeetingCatalogController
{
    protected function modelClass(): string
    {
        return MeetingDocumentField::class;
    }

    protected function successLabel(): string
    {
        return 'lĩnh vực tài liệu họp';
    }

    protected function fileName(): string
    {
        return 'meeting-document-fields.xlsx';
    }
}
