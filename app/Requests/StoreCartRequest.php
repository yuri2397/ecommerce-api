<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id'
            ],
            'status' => [
                'nullable',
                'string',
                'in:active,converted,abandoned'
            ]
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'L\'ID utilisateur est requis.',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'status.in' => 'Le statut doit être l\'un des suivants : active, converted, abandoned.'
        ];
    }

    public function authorize()
    {
        // Dans un contexte réel, vous voudriez vérifier que l'utilisateur a les permissions nécessaires
        // Par exemple, seul un admin devrait pouvoir créer un panier pour un autre utilisateur
        return true;
    }
}
