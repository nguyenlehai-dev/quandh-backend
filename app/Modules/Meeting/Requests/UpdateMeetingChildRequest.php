<?php

namespace App\Modules\Meeting\Requests;

class UpdateMeetingChildRequest extends StoreMeetingChildRequest
{
    public function rules(): array
    {
        return $this->rulesFor((string) $this->route('child'), true);
    }
}
