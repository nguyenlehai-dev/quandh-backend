<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MeetingChildCollection extends ResourceCollection
{
    public $collects = MeetingChildResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
