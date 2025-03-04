<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\UsesUuid;


class Payment extends Model
{
    use UsesUuid;

    protected $fillable = [
        'order_id',
        'payment_method',
        'transaction_id',
        'amount',
        'status',
        'payment_details'
    ];

    // Commande associée au paiement
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
