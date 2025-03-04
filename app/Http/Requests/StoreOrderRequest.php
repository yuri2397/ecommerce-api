<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_address' => [
                'required',
                'string',
                'max:255'
            ],
            'billing_address' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    public function messages()
    {
        return [
            'shipping_address.required' => 'L\'adresse de livraison est requise.',
            'shipping_address.max' => 'L\'adresse de livraison ne peut pas dépasser :max caractères.',
            'billing_address.max' => 'L\'adresse de facturation ne peut pas dépasser :max caractères.'
        ];
    }

    public function authorize()
    {
        // L'utilisateur doit être authentifié pour créer une commande
        return true;
    }
}
