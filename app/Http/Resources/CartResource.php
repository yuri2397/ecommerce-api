<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        // Calculer le total du panier
        $total = 0;
        $itemsCount = 0;

        if ($this->relationLoaded('cartItems') && $this->cartItems->isNotEmpty()) {
            $itemsCount = $this->cartItems->sum('quantity');

            foreach ($this->cartItems as $item) {
                if ($item->relationLoaded('product')) {
                    $price = $item->product->sale_price ?? $item->product->price;
                    $total += $price * $item->quantity;
                }
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'items_count' => $itemsCount,
            'total_amount' => $total,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'items' => $this->when($this->relationLoaded('cartItems'), function () {
                return CartItemResource::collection($this->cartItems);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
