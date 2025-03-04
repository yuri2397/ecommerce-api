<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        $subtotal = 0;

        if ($this->relationLoaded('product')) {
            $price = $this->product->sale_price ?? $this->product->price;
            $subtotal = $price * $this->quantity;
        }

        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'product' => $this->when($this->relationLoaded('product'), function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'price' => $this->product->price,
                    'sale_price' => $this->product->sale_price,
                    'thumbnail_url' => $this->product->getFirstMediaUrl('product_thumbnail'),
                    'in_stock' => $this->product->stock_quantity > 0,
                    'stock_quantity' => $this->product->stock_quantity,
                ];
            }),
            'unit_price' => $this->when($this->relationLoaded('product'), function () {
                return $this->product->sale_price ?? $this->product->price;
            }),
            'subtotal' => $subtotal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
