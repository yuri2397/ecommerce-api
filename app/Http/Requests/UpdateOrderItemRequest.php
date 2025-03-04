<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
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
