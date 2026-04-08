<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CatalogCollection extends ResourceCollection
{
    public $collects = CatalogResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
