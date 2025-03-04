<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;

class ProductComment extends Model
{
    use UsesUuid;

    protected $fillable = [
        'product_id',
        'user_id',
        'content',
        'rating'
    ];

    // Produit commenté
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Utilisateur ayant laissé le commentaire
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
