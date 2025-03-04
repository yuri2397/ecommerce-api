<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Utils\UsesUuid;

class CartItem extends Model
{
    use UsesUuid;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity'
    ];

    // Panier auquel appartient l'article
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    // Produit de l'article
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
