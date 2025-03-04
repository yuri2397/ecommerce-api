<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:active,converted,abandoned'
            ]
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'Le statut doit être l\'un des suivants : active, converted, abandoned.'
        ];
    }

    public function authorize()
    {
        // Dans un contexte réel, vous voudriez vérifier que l'utilisateur a les permissions nécessaires
        // ou qu'il est bien le propriétaire du panier
        return true;
    }
}
