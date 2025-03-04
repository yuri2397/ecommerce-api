<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_method' => [
                'sometimes',
                'string',
                'in:credit_card,paypal,bank_transfer,cash'
            ],
            'transaction_id' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('payments')->ignore($this->route('payment'))
            ],
            'amount' => [
                'sometimes',
                'numeric',
                'min:0.01'
            ],
            'status' => [
                'sometimes',
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
            'payment_method.in' => 'La méthode de paiement doit être l\'une des suivantes : credit_card, paypal, bank_transfer, cash.',
            'transaction_id.unique' => 'Cet ID de transaction a déjà été utilisé.',
            'amount.min' => 'Le montant doit être au moins 0.01.',
            'status.in' => 'Le statut doit être l\'un des suivants : pending, completed, failed.'
        ];
    }

    public function authorize()
    {
        // Dans un contexte réel, vous vérifieriez si l'utilisateur a le rôle admin
        return true;
    }
}
