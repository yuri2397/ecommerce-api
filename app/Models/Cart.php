<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;


class Cart extends Model
{
    use UsesUuid;

    protected $fillable = [
        'user_id',
        'status'
    ];

    // Utilisateur du panier
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Articles du panier
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Produits dans le panier
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'cart_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
