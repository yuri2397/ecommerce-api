<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id'
            ],
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id'
            ],
            'content' => [
                'required',
                'string',
                'min:3',
                'max:1000'
            ],
            'rating' => [
                'nullable',
                'integer',
                'min:1',
                'max:5'
            ]
        ];
    }

    public function messages()
    {
        return [
            'product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'content.required' => 'Le contenu du commentaire est obligatoire.',
            'content.min' => 'Le commentaire doit contenir au moins :min caractères.',
            'content.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
            'rating.min' => 'La note doit être au minimum :min.',
            'rating.max' => 'La note ne peut pas dépasser :max.'
        ];
    }

    public function authorize()
    {
        // Vous pourriez ajouter ici une vérification pour s'assurer que l'utilisateur
        // est autorisé à ajouter un commentaire pour ce produit
        return true;
    }
}
