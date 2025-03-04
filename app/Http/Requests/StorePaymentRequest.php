<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                'in:credit_card,paypal,bank_transfer,cash'
            ],
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
                'unique:payments,transaction_id'
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],
            'status' => [
                'nullable',
                'string',
                'in:pending,completed,failed'
            ],
            'payment_details' => [
                'nullable',
                'array'
            ]
        ];
    }

    public function messages()
    {
        return [
            'payment_method.required' => 'La méthode de paiement est requise.',
            'payment_method.in' => 'La méthode de paiement doit être l\'une des suivantes : credit_card, paypal, bank_transfer, cash.',
            'transaction_id.unique' => 'Cet ID de transaction a déjà été utilisé.',
            'amount.required' => 'Le montant est requis.',
            'amount.min' => 'Le montant doit être au moins 0.01.',
            'status.in' => 'Le statut doit être l\'un des suivants : pending, completed, failed.'
        ];
    }

    public function authorize()
    {
        // Pour une version publique (client), on vérifie que l'utilisateur est bien le propriétaire de la commande
        // Pour une version admin, on vérifie le rôle
        return true;
    }
}
