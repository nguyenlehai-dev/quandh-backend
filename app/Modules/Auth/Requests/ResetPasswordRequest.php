<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate reset password request using token from email.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'token' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng.',
            'email.email' => 'Email khÃ´ng há»£p lá»‡.',
            'password.required' => 'Máº­t kháº©u khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng.',
            'password.min' => 'Máº­t kháº©u pháº£i cÃ³ Ã­t nháº¥t 6 kÃ½ tá»±.',
            'password.confirmed' => 'Máº­t kháº©u xÃ¡c nháº­n khÃ´ng khá»›p.',
            'token.required' => 'Token Ä‘áº·t láº¡i máº­t kháº©u khÃ´ng Ä‘Æ°á»£c Ä‘á»ƒ trá»‘ng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email tai khoan can dat lai mat khau.',
                'example' => 'user@example.com',
            ],
            'password' => [
                'description' => 'Mat khau moi.',
                'example' => 'newpassword123',
            ],
            'password_confirmation' => [
                'description' => 'Xac nhan mat khau moi.',
                'example' => 'newpassword123',
            ],
            'token' => [
                'description' => 'Token nhan tu email reset mat khau.',
                'example' => 'sample-reset-token',
            ],
        ];
    }
}
