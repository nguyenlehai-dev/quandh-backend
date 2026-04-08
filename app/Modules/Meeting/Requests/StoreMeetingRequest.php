<?php

namespace App\Modules\Meeting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meeting_type_id' => ['nullable', 'integer', 'exists:m_meeting_types,id'],
            'code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['nullable', 'in:draft,active,in_progress,completed,cancelled'],
            'active_agenda_id' => ['nullable', 'integer', 'exists:m_agendas,id'],
        ];
    }
}
