<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string'
            ],
            'price' => [
                'sometimes',
                'numeric',
                'min:0'
            ],
            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:price'
            ],
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->ignore($this->route('product'))
            ],
            'stock_quantity' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'category_id' => [
                'sometimes',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                })
            ],
            'is_active' => [
                'nullable',
                'boolean'
            ],
            'is_featured' => [
                'nullable',
                'boolean'
            ],
            'images' => [
                'nullable',
                'array'
            ],
            'images.*' => [
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120' // 5MB
            ],
            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048' // 2MB
            ],
            'replace_images' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function messages()
    {
        return [
            'price.min' => 'Le prix doit être supérieur ou égal à 0.',
            'sale_price.lte' => 'Le prix promotionnel doit être inférieur ou égal au prix normal.',
            'sku.unique' => 'Ce SKU est déjà utilisé par un autre produit.',
            'category_id.exists' => 'La catégorie sélectionnée doit être active.'
        ];
    }
}
