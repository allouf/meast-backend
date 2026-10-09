<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * RFQ / custom request category (distinct from {@see ServiceCategory}).
 */
class CustomRequestCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'custom_request_categories';

    protected $fillable = [
        'name',
    ];

    protected $translatable = ['name'];

    public function customRequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class, 'category_id');
    }
}
