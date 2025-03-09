<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($this->route('category'))
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                })
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Une catégorie avec ce nom existe déjà.',
            'parent_id.exists' => 'La catégorie parente doit être active.'
        ];
    }
}
