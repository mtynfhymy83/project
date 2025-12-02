<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'شناسه کتاب الزامی است',
            'id.integer' => 'شناسه کتاب باید عدد باشد',
            'id.min' => 'شناسه کتاب نامعتبر است',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

class BookListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:categories,id',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:latest,popular,rating',
            'free_only' => 'nullable|boolean',
            'special_only' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'دسته‌بندی وجود ندارد',
            'sort.in' => 'نوع مرتب‌سازی نامعتبر است',
            'per_page.max' => 'حداکثر تعداد در هر صفحه 100 است',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

class ReadContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|integer|exists:books,id',
            'page_number' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'شناسه کتاب الزامی است',
            'book_id.exists' => 'کتاب یافت نشد',
            'page_number.required' => 'شماره صفحه الزامی است',
            'page_number.min' => 'شماره صفحه باید حداقل 1 باشد',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'لطفاً وارد شوید'
            ], 401)
        );
    }
}
