<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;
use Modules\Job\Models\Language;
use Modules\Services\Enums\ProviderVerificationStatus;

class ServiceProvider extends Model
{
    protected $table = 'service_providers';

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_COMPANY = 'company';

    protected $fillable = [
        'user_id',
        'type',
        'display_name',
        'logo',
        'country_code',
        'region',
        'bio',
        'cover',
        'identity_document',
        'verification_notes',
        'is_verified',
        'verification_status',
        'avg_rating',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verification_status' => ProviderVerificationStatus::class,
        'avg_rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (ServiceProvider $provider): void {
            if ($provider->isDirty('verification_status')) {
                $status = $provider->verification_status;
                $provider->is_verified = $status instanceof ProviderVerificationStatus
                    ? $status->isApproved()
                    : (string) $status === ProviderVerificationStatus::Approved->value;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'service_provider_language')
            ->withTimestamps();
    }

    public function serviceOffers()
    {
        return $this->hasMany(ServiceOffer::class);
    }

    public function projects()
    {
        return $this->hasMany(ProviderProject::class)->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class, 'service_provider_id')
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'provider_id');
    }

    public function customRequestOrders()
    {
        return $this->hasMany(Order::class, 'provider_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

}
