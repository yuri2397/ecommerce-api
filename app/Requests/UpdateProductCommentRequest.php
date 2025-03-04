<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => [
                'sometimes',
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
            'content.min' => 'Le commentaire doit contenir au moins :min caractères.',
            'content.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
            'rating.min' => 'La note doit être au minimum :min.',
            'rating.max' => 'La note ne peut pas dépasser :max.'
        ];
    }

    public function authorize()
    {
        // Vérifier que l'utilisateur authentifié est bien l'auteur du commentaire
        // ou qu'il a les droits d'administration nécessaires
        $comment = $this->route('comment');

        // Dans un cas réel, vous vérifieriez ici si l'utilisateur actuel
        // est le propriétaire du commentaire ou a des droits d'administrateur
        return true;
    }
}
