<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'photo',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'verified_at',
        'country_code',
        'region',
        'ref_code',
        'referred_by_user_id',
        'is_active',
        'is_profile_complete',
        'deletion_reason',
        'is_beta_user',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'verified_at' => 'datetime',
        'is_profile_complete' => 'boolean',
        'is_beta_user' => 'boolean',
    ];

    protected $dates = [
        'verified_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'provider_token',
        'provider_refresh_token',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }


    public function freelancer()
    {
        return $this->hasOne(Freelancer::class);
    }
    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function storeMemberships()
    {
        return $this->hasMany(StoreMember::class);
    }

    public function activeStoreMemberships()
    {
        return $this->hasMany(StoreMember::class)->where('status', 'active');
    }



    /*--------------------------------------------------------------------------------------------- */
    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }


    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'channel_members')
            ->withPivot(['role', 'status', 'joined_at', 'last_read_at', 'is_online'])
            ->withTimestamps();
    }

    public function ownedChannels()
    {
        return $this->hasMany(Channel::class, 'owner_id');
    }

    public function channelMemberships()
    {
        return $this->hasMany(ChannelMember::class);
    }

    public function channelMessages()
    {
        return $this->hasMany(ChannelMessage::class, 'sender_id');
    }

    public function channelMemberRequests()
    {
        return $this->hasMany(ChannelMemberRequest::class);
    }

    public function reviewedChannelRequests()
    {
        return $this->hasMany(ChannelMemberRequest::class, 'reviewed_by');
    }

    public function referrals()
    {
        return $this->hasMany(ReferralLog::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function points()
    {
        return $this->hasOne(UserPoint::class);
    }

    public function rewards()
    {
        return $this->hasMany(UserReward::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function getReferralLinkAttribute()
    {
        if (!$this->ref_code) return null;

        $baseUrl = config('app.frontend_register_url', 'https://uat.middleeastcommerce.net/register');

        return $baseUrl . '?ref=' . $this->ref_code;
    }
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function couponsCreated()
    {
        return $this->hasMany(Coupon::class, 'seller_id');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class)->where('is_active', true);
    }

    public function masterOrders()
    {
        return $this->hasMany(MasterOrder::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Job applications submitted by this user (candidate).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<JobApplication, $this>
     */
    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'candidate_id');
    }

    public function workExperiences()
    {
        return $this->hasMany(UserWorkExperience::class);
    }

    public function educations()
    {
        return $this->hasMany(UserEducation::class);
    }

    public function languages()
    {
        return $this->hasMany(UserLanguage::class);
    }

    public function skills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function certifications()
    {
        return $this->hasMany(UserCertification::class);
    }

    public function projects()
    {
        return $this->hasMany(UserProject::class);
    }

    public function links()
    {
        return $this->hasMany(UserLink::class);
    }
}
