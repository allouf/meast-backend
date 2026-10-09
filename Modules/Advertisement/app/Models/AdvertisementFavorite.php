<?php

namespace Modules\Advertisement\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisementFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'advertisement_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }
}
