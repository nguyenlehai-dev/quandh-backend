<?php

namespace App\Modules\Meeting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ];

        if (in_array($resource, ['attendee-groups', 'meeting-document-types'], true)) {
            $rules['meeting_type_id'] = ['nullable', 'integer', 'exists:m_meeting_types,id'];
        }

        if ($resource === 'meeting-document-signers') {
            $rules['position'] = ['nullable', 'string', 'max:255'];
        }

        if ($resource === 'attendee-groups') {
            $rules['members'] = ['nullable', 'array'];
            $rules['members.*.user_id'] = ['required_with:members', 'integer', 'exists:users,id'];
            $rules['members.*.position'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
