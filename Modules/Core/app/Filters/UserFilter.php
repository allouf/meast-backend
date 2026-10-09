<?php

namespace Modules\Core\Filters;

use Filterable\Filter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends Filter
{
    public function apply(Builder $builder, ?array $options = []): Builder
    {
        $filters = request()->only([
            'name',
            'email',
            'type',
            'is_active',
            'provider',
            'gender',
            'region',
            'country_code',
            'phone',
            'verified',
            'role',
            'search',
            'company_name',
            'registration_no'
        ]);
        $builder->whereNull('deletion_reason');
        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $builder->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('company', function ($q) use ($searchTerm) {
                        $q->where('company_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('registration_no', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        if (isset($filters['name'])) {
            $builder->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['email'])) {
            $builder->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (isset($filters['phone'])) {
            $builder->where('phone', 'like', '%' . $filters['phone'] . '%');
        }

        if (isset($filters['type'])) {
            $builder->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['provider'])) {
            $builder->where('provider', $filters['provider']);
        }

        if (isset($filters['gender'])) {
            $builder->where('gender', $filters['gender']);
        }

        if (isset($filters['country_code'])) {
            $builder->where('country_code', $filters['country_code']);
        }

        if (isset($filters['region'])) {
            $builder->where('region', 'LIKE', '%' . $filters['region'] . '%');
        }


        // Role-based filtering
        if (isset($filters['role'])) {
            $builder->whereHas('roles', function ($query) use ($filters) {
                $query->where('name', $filters['role']);
            });
        }

        // Company-specific filters
        if (isset($filters['company_name'])) {
            $builder->whereHas('company', function ($query) use ($filters) {
                $query->where('company_name', 'like', '%' . $filters['company_name'] . '%');
            });
        }

        if (isset($filters['registration_no'])) {
            $builder->whereHas('company', function ($query) use ($filters) {
                $query->where('registration_no', 'like', '%' . $filters['registration_no'] . '%');
            });
        }

        return $builder;
    }
}
