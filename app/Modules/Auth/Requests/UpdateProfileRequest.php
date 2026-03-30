<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên hiển thị không được để trống.',
            'name.max' => 'Tên hiển thị không được vượt quá 255 ký tự.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Tên hiển thị của người dùng.',
                'example' => 'Nguyễn Văn A',
            ],
            'email' => [
                'description' => 'Email duy nhất của người dùng.',
                'example' => 'user@example.com',
            ],
        ];
    }
}
