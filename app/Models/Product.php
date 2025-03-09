<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use UsesUuid, InteractsWithMedia;
    protected $fillable = [
        'name',
        'description',
        'price',
        'sale_price',
        'sku',
        'stock_quantity',
        'category_id',
        'is_active',
        'is_featured',
        'discount_percentage'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Catégorie du produit
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Commentaires du produit
    public function comments(): HasMany
    {
        return $this->hasMany(ProductComment::class);
    }

    // Commandes contenant ce produit
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Paniers contenant ce produit
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
