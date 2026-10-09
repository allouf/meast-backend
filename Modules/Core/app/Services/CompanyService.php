<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Company;
use Modules\Core\Models\User;
use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Repositories\Interfaces\CompanyRepositoryInterface;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;

class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $companyRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function all(Request $request = null)
    {
        if ($request && $request->hasAny(['search', 'name', 'email', 'phone', 'is_active', 'verified_at', 'region', 'country_code', 'company_name', 'registration_no'])) {
            $request->merge(['role' => 'company']);
            return $this->userRepository->filter($request);
        }
        return $this->companyRepository->all();
    }

    public function getDetails(int $id)
    {
        return $this->companyRepository->findById($id);
    }

    public function createCompany(array $data)
    {
        if (isset($data['contact'])) {
            if (filter_var($data['contact'], FILTER_VALIDATE_EMAIL)) {
                $data['email'] = $data['contact'];
                $data['phone'] = null;
            } else {
                $data['phone'] = $data['contact'];
                $data['email'] = null;
            }
        }
        return DB::transaction(function () use ($data) {
            $userData = [
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'verified_at' => Auth::guard('admin')->check() ? now() : null,
                'country_code' => $data['country_code'] ?? null,
                'region' => $data['region'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];
            $user = $this->userRepository->create($userData);
            $logo = isset($data['logo']) ? FileManager::upload('companies', $data['logo']) : null;
            $tradeLicense = isset($data['trade_license']) ? FileManager::upload('companies', $data['trade_license']) : null;
            $companyData = [
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'registration_no' => $data['registration_no'] ?? null,
                'vat_tax_id' => $data['vat_tax_id'] ?? null,
                'logo' => $logo,
                'trade_license' => $tradeLicense,
                'website' => $data['website'] ?? null,
            ];
            $company = $this->companyRepository->create($companyData);
            $user->assignRole('company');
            return $company->load('user');
        });
    }

    public function updateCompany(Company $company, array $data)
    {
        return $this->companyRepository->update($company, $data);
    }

    public function completeCompanyInfo(User $user, array $data)
    {
        $companyData = [
            'user_id' => $user->id,
            'company_name' => $data['company_name'] ?? null,
            'registration_no' => $data['registration_no'] ?? null,
            'vat_tax_id' => $data['vat_tax_id'] ?? null,
            'logo' => $data['logo'] ?? null,
            'trade_license' => $data['trade_license'] ?? null,
            'website' => $data['website'] ?? null,
        ];
        $company = $this->companyRepository->create($companyData);
        return $company->load('user');
    }
}
