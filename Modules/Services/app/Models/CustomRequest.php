<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Models\User;
use Modules\Services\Enums\CustomRequestDeliveryModel;
use Modules\Services\Enums\CustomRequestStatus;

class CustomRequest extends Model
{
    use HasFactory;

    protected $table = 'custom_requests';

    protected $fillable = [
        'client_id',
        'category_id',
        'reference_number',
        'title',
        'description',
        'delivery_model',
        'deadline_date',
        'budget_min',
        'budget_max',
        'status',
    ];

    protected $casts = [
        'delivery_model' => CustomRequestDeliveryModel::class,
        'deadline_date' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'status' => CustomRequestStatus::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomRequestCategory::class, 'category_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'rfq_id');
    }
}
