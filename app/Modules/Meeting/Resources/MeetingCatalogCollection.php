<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MeetingCatalogCollection extends ResourceCollection
{
    public $collects = MeetingCatalogResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
