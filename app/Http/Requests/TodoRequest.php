<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TodoRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'content' => 'nullable|string|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'required|date',
            'category_id' => 'nullable|integer|exists:categories,id',
            'priority' => 'nullable|integer|between:1,3',
            'parent_id' => 'nullable|integer|exists:todos,id',
            'image' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/jpg,image/gif,image/webp',
                'max:2048',
                'dimensions:max_width=4000,max_height=4000',
            ],
        ];
    }
}
