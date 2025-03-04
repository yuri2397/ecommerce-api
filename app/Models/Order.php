<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;

class Order extends Model
{
    use UsesUuid;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'shipping_address',
        'billing_address'
    ];

    // Utilisateur ayant passé la commande
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Articles de la commande
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Paiements associés à la commande
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Produits de la commande
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
