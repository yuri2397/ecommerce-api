<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id,is_active,1'
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1'
            ],
            'replace' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'L\'ID du produit est requis.',
            'product_id.exists' => 'Le produit sélectionné n\'existe pas ou n\'est pas actif.',
            'quantity.required' => 'La quantité est requise.',
            'quantity.min' => 'La quantité doit être au moins 1.'
        ];
    }

    public function authorize()
    {
        // L'utilisateur doit être authentifié pour ajouter au panier
        return true;
    }
}
