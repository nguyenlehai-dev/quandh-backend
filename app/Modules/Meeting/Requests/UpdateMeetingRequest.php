<?php

namespace App\Modules\Meeting\Requests;

class UpdateMeetingRequest extends StoreMeetingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'][0] = 'sometimes';
        $rules['start_at'][0] = 'sometimes';

        return $rules;
    }
}
