<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'channel' => $this->channel,
            'remind_at' => $this->remind_at?->format('H:i:s d/m/Y'),
            'status' => $this->status,
            'payload' => $this->payload,
            'sent_at' => $this->sent_at?->format('H:i:s d/m/Y'),
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
