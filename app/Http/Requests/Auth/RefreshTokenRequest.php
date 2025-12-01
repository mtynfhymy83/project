<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RefreshTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'refresh_token' => 'required|string|size:64',
            'device_name' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'refresh_token.required' => 'توکن تازه‌سازی الزامی است',
            'refresh_token.string' => 'فرمت توکن تازه‌سازی نامعتبر است',
            'refresh_token.size' => 'طول توکن تازه‌سازی باید 64 کاراکتر باشد',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
