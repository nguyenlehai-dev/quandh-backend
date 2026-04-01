<?php

namespace App\Modules\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_email' => 'boolean',
            'notify_system' => 'boolean',
            'notify_meeting_reminder' => 'boolean',
            'notify_vote' => 'boolean',
            'notify_document' => 'boolean',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'notify_email' => [
                'description' => 'Bat/tat thong bao qua email.',
                'example' => true,
            ],
            'notify_system' => [
                'description' => 'Bat/tat thong bao trong he thong.',
                'example' => true,
            ],
            'notify_meeting_reminder' => [
                'description' => 'Bat/tat thong bao nhac lich hop.',
                'example' => true,
            ],
            'notify_vote' => [
                'description' => 'Bat/tat thong bao lien quan den bieu quyet.',
                'example' => true,
            ],
            'notify_document' => [
                'description' => 'Bat/tat thong bao lien quan den tai lieu.',
                'example' => true,
            ],
        ];
    }
}
