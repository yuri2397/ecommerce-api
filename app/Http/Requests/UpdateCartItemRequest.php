<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCartItemRequest extends FormRequest
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
        // Vérifier que l'utilisateur est propriétaire du panier contenant cet article
        $cartItem = $this->route('cartItem');
        $user = Auth::user();

        // Si on ne peut pas récupérer l'élément du panier ou l'utilisateur courant, refuser l'accès
        if (!$cartItem || !$user) {
            return false;
        }

        // Vérifier que l'article appartient à un panier de l'utilisateur courant
        return $cartItem->cart->user_id === $user->id;
    }
}
