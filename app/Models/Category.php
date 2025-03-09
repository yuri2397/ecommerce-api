<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    const ICON_COLLECTION = 'icons';
    use UsesUuid, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'parent_id',
        'is_active'
    ];

    protected $appends = [
        'products_count',
        'icon_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getIconUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::ICON_COLLECTION);
    }

    // Sous-catégories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Catégorie parente
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Produits de la catégorie
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getProductsCountAttribute()
    {
        return $this->products()->where('is_active', true)->count();
    }
}
