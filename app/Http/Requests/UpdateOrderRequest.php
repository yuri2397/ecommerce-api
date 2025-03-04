<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:pending,processing,shipped,delivered,cancelled'
            ],
            'shipping_address' => [
                'sometimes',
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
            'status.in' => 'Le statut doit être l\'un des suivants : pending, processing, shipped, delivered, cancelled.',
            'shipping_address.max' => 'L\'adresse de livraison ne peut pas dépasser :max caractères.',
            'billing_address.max' => 'L\'adresse de facturation ne peut pas dépasser :max caractères.'
        ];
    }

    public function authorize()
    {
        // Dans un contexte réel, vous vérifieriez si l'utilisateur a le rôle admin
        return true;
    }
}
