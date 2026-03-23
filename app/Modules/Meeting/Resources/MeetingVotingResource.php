<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingVotingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'meeting_agenda_id' => $this->meeting_agenda_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'agenda_title' => $this->whenLoaded('agenda', fn () => $this->agenda->title),
            'results_summary' => $this->whenLoaded('results', function () {
                $results = $this->results;

                return [
                    'total' => $results->count(),
                    'agree' => $results->where('choice', 'agree')->count(),
                    'disagree' => $results->where('choice', 'disagree')->count(),
                    'abstain' => $results->where('choice', 'abstain')->count(),
                ];
            }),
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
