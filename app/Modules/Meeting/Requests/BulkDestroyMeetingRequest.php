<?php

namespace App\Modules\Meeting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:m_meetings,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách ID không được để trống.',
            'ids.array' => 'Danh sách ID phải là mảng.',
            'ids.*.exists' => 'Một hoặc nhiều cuộc họp không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
