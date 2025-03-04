<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id'
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1'
            ]
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'L\'ID du produit est requis.',
            'product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'quantity.required' => 'La quantité est requise.',
            'quantity.min' => 'La quantité doit être au moins 1.'
        ];
    }

    public function authorize()
    {
        // Dans un contexte réel, vous vérifieriez si l'utilisateur a le rôle admin
        return true;
    }
}
