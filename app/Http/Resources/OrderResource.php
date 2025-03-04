<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        // Calculer les statistiques de paiement
        $totalPaid = 0;
        $paymentStatus = 'pending';

        if ($this->relationLoaded('payments')) {
            $completedPayments = $this->payments->where('status', 'completed');
            $totalPaid = $completedPayments->sum('amount');

            if ($totalPaid >= $this->total_amount) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partially_paid';
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'payment_status' => $paymentStatus,
            'total_paid' => $totalPaid,
            'balance_due' => $this->total_amount - $totalPaid,
            'items_count' => $this->when($this->relationLoaded('orderItems'), function () {
                return $this->orderItems->sum('quantity');
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'items' => $this->when($this->relationLoaded('orderItems'), function () {
                return OrderItemResource::collection($this->orderItems);
            }),
            'payments' => $this->when($this->relationLoaded('payments'), function () {
                return PaymentResource::collection($this->payments);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
