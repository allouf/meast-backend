<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Job\Models\JobApplication;
use Modules\Job\Models\UserCertification;
use Modules\Job\Models\UserEducation;
use Modules\Job\Models\UserLanguage;
use Modules\Job\Models\UserLink;
use Modules\Job\Models\UserProject;
use Modules\Job\Models\UserSkill;
use Modules\Job\Models\UserWorkExperience;
use Modules\Store\Models\Cart;
use Modules\Store\Models\Coupon;
use Modules\Store\Models\CouponUsage;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreMember;
use Modules\Store\Models\UserAddress;
use Modules\Store\Models\Wishlist;
use Modules\Community\Models\Post;
use Modules\Community\Models\Comment;
use Modules\Community\Models\Reaction;
use Illuminate\Support\Facades\DB;
use Modules\Services\Models\ServiceProvider;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles;

    protected $guard_name = 'api';

    /**
     * Role name for API responses (api guard first, then any assigned role).
     */
    public function getDisplayRoleName(): ?string
    {
        $name = $this->getRoleNames()->first();
        if ($name !== null) {
            return $name;
        }
        $rolesTable = config('permission.table_names.roles', 'roles');
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelKey = config('permission.column_names.model_morph_key', 'model_id');
        $rolePivotKey = config('permission.column_names.role_pivot_key', 'role_id') ?? 'role_id';

        return DB::table($pivotTable)
            ->where('model_type', static::class)
            ->where($modelKey, $this->getKey())
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivotTable}.{$rolePivotKey}")
            ->value("{$rolesTable}.name");
    }

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
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }


    public function company()
    {
        return $this->hasOne(Company::class);
    }

    /** Job profile (name, bio, avatar, cover, gallery) for candidate/company. */
    public function jobProfile()
    {
        return $this->hasOne(\Modules\Job\Models\JobProfile::class);
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
    public function jobApplications(): \Illuminate\Database\Eloquent\Relations\HasMany
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

    public function serviceProvider()
    {
        return $this->hasOne(ServiceProvider::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
