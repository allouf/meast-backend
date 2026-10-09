<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Services\Enums\QuoteStatus;

class Quote extends Model
{
    use HasFactory;

    protected $table = 'custom_request_quotes';

    protected $fillable = [
        'rfq_id',
        'provider_id',
        'proposed_budget',
        'estimated_delivery_date',
        'cover_letter_or_message',
        'status',
    ];

    protected $casts = [
        'proposed_budget' => 'decimal:2',
        'estimated_delivery_date' => 'date',
        'status' => QuoteStatus::class,
    ];

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'rfq_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->provider();
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'quote_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
