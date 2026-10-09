<?php

namespace Modules\Core\Services;

use Modules\Core\Models\User;
use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected CompanyService $companyService
    ) {}

    public function getConsumers(Request $request = null)
    {
        if ($request && $request->hasAny(['search', 'name', 'email', 'phone', 'is_active', 'verified_at', 'region', 'country_code'])) {
            $request->merge(['role' => 'consumer']);
            return $this->userRepository->filter($request);
        }
        return $this->userRepository->getConsumers();
    }

    public function getUserByEmail(string $email)
    {
        return $this->userRepository->findByEmail($email);
    }

    public function getUserByPhone(string $phone)
    {
        return $this->userRepository->findByPhone($phone);
    }

    public function getUserByContact(string $contact)
    {
        return filter_var($contact, FILTER_VALIDATE_EMAIL)
            ? $this->getUserByEmail($contact)
            : $this->getUserByPhone($contact);
    }

    public function findById(int $id)
    {
        return $this->userRepository->findById($id);
    }

    public function createConsumer(array $data)
    {
        $data['is_active'] = $data['is_active'] ?? true;
        if (isset($data['contact']) && filter_var($data['contact'], FILTER_VALIDATE_EMAIL)) {
            $data['email'] = $data['contact'];
            $data['phone'] = null;
        } elseif (isset($data['contact']) && !filter_var($data['contact'], FILTER_VALIDATE_EMAIL)) {
            $data['phone'] = $data['contact'];
            $data['email'] = null;
        }
        $data['verified_at'] = Auth::guard('admin')->check() ? now() : null;
        $data['password'] = Hash::make($data['password']);
        $consumer = $this->userRepository->create($data);
        $consumer->assignRole('consumer');
        return $consumer;
    }

    public function markVerified(User $user)
    {
        return $this->userRepository->markVerified($user);
    }

    public function updateProfile(User $user, array $data)
    {
        $userData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'country_code' => $data['country_code'] ?? $user->country_code,
            'region' => $data['region'] ?? $user->region,
        ];
        if (!empty($data['password'])) {
            $userData['password'] = bcrypt($data['password']);
        }
        if (!empty($data['photo'])) {
            $userData['photo'] = FileManager::upload('consumers', $data['photo']);
        }
        $userData['is_profile_complete'] = true;
        $this->userRepository->update($user, $userData);
        if ($user->hasRole('company')) {
            $company = $user->company;
            $companyData = [
                'company_name' => $data['company_name'] ?? $company?->company_name,
                'registration_no' => $data['registration_no'] ?? $company?->registration_no,
                'vat_tax_id' => $data['vat_tax_id'] ?? $company?->vat_tax_id,
                'website' => $data['website'] ?? $company?->website,
            ];
            if (!empty($data['logo'])) {
                $companyData['logo'] = FileManager::upload('companies', $data['logo']);
            }
            if (!empty($data['trade_license'])) {
                $companyData['trade_license'] = FileManager::upload('companies', $data['trade_license']);
            }
            if ($user->company) {
                $this->companyService->updateCompany($user->company, $companyData);
            } else {
                $companyData['user_id'] = $user->id;
                $this->companyService->completeCompanyInfo($user, $companyData);
            }
        }
        return $user->fresh(['company']);
    }

    public function resetPassword(User $user, string $newPassword)
    {
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        return $this->userRepository->updatePassword($user, Hash::make($newPassword));
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        if (!Hash::check($currentPassword, $user->password)) {
            throw new HttpException(400, 'Current password is incorrect');
        }
        return $this->userRepository->updatePassword($user, Hash::make($newPassword));
    }

    public function updateRole($user, string $role): void
    {
        try {
            $this->userRepository->updateRole($user, $role);
        } catch (\Exception $e) {
            throw new HttpException(400, 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function deleteProfilePhoto(User $user)
    {
        if (!empty($user->photo)) {
            FileManager::delete($user->photo);
            $this->userRepository->update($user, ['photo' => null]);
        }
        return $user->fresh(['company']);
    }

    public function uploadProfilePhoto(User $user, UploadedFile $photo)
    {
        if (!empty($user->photo)) {
            FileManager::delete($user->photo);
        }
        $path = FileManager::upload('consumers', $photo);
        $this->userRepository->update($user, ['photo' => $path]);
        return $user->fresh(['company']);
    }

    public function deleteAccount(User $user, string $password, ?string $reason = null): void
    {
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        if (!Hash::check($password, $user->password)) {
            throw new HttpException(422, 'Invalid password');
        }
        if ($user->deletion_reason) {
            throw new HttpException(400, 'Account has already been deleted');
        }
        $this->anonymizeUser($user, $reason);
    }

    private function anonymizeUser(User $user, ?string $reason = null): void
    {
        $deletedAt = now();
        $userData = [
            'name' => 'Deleted User ' . $user->id,
            'email' => 'deleted_' . $deletedAt->timestamp . '_' . $user->id . '@deleted.local',
            'phone' => 'deleted_' . $deletedAt->timestamp . '_' . $user->id,
            'verified_at' => null,
            'is_active' => false,
            'deletion_reason' => $reason ?? 'User requested account deletion',
        ];
        if (!empty($user->photo)) {
            FileManager::delete($user->photo);
            $userData['photo'] = null;
        }
        $this->userRepository->update($user, $userData);
        if ($user->company) {
            $user->company->update(['company_name' => 'Deleted Company ' . $user->company->id]);
        }
    }
}
