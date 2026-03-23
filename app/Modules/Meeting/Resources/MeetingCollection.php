<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MeetingCollection extends ResourceCollection
{
    public $collects = MeetingResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
