<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utils\UsesUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Shop extends Model implements HasMedia
{
    use UsesUuid, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'is_active',
        'user_id'
    ];

    protected $appends = [
        'logo_url'
    ];

    public function getLogoUrlAttribute()
    {
        return $this->getFirstMediaUrl('logo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
