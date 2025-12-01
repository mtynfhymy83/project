<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EitaaAuthRequest extends FormRequest
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
            'eitaa_data' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // بررسی وجود پارامترهای ضروری
                    parse_str($value, $params);

                    if (!isset($params['auth_date'])) {
                        $fail('auth_date is required in eitaa_data');
                    }

                    if (!isset($params['hash'])) {
                        $fail('hash is required in eitaa_data');
                    }

                    if (!isset($params['user'])) {
                        $fail('user data is required in eitaa_data');
                    }
                }
            ],
            'device_name' => 'nullable|string|max:100',
            'device_type' => 'nullable|string|in:mobile,tablet,desktop',
            'platform' => 'nullable|string|in:ios,android,web',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'eitaa_data.required' => 'داده‌های ایتا الزامی است',
            'eitaa_data.string' => 'فرمت داده‌های ایتا نامعتبر است',
            'device_type.in' => 'نوع دستگاه باید یکی از مقادیر mobile, tablet یا desktop باشد',
            'platform.in' => 'پلتفرم باید یکی از مقادیر ios, android یا web باشد',
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

    /**
     * Get the validated data with cleaned eitaa_data
     */
    public function validatedWithCleaning(): array
    {
        $validated = $this->validated();

        // حذف backslash های اضافی از eitaa_data
        $validated['eitaa_data'] = preg_replace('/\\\\"/', '"', $validated['eitaa_data']);

        return $validated;
    }
}
