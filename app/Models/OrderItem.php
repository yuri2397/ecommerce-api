<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;

class OrderItem extends Model
{
    use UsesUuid;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price'
    ];

    // Commande à laquelle appartient l'article
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Produit de l'article
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
