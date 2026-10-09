<?php

namespace App\Services;

use App\Mail\ConfirmUpgradeMail;
use App\Models\User;
use App\Repositories\Interfaces\FreelancerRepositoryInterface;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;
use App\Utilities\FileManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class FreelancerService
{
    public function __construct(
        protected FreelancerRepositoryInterface $freelancerRepository,
        protected UserRepositoryInterface $userRepository
    ) {}
    public function all()
    {
        return $this->freelancerRepository->all();
    }
    public function createFreelancer(array $data)
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
                'name'      => $data['name'],
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'password'  => isset($data['password']) ? Hash::make($data['password']) : null,
                'country_code' => $data['country_code'] ?? null,
                'region' => $data['region'] ?? null,
                'ref_code'  => $data['ref_code'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];

            $user = $this->userRepository->create($userData);

            $freelancerData = [
                'user_id'       => $user->id,
                'profession_id' => $data['profession_id'],
                'national_id'   => $data['national_id'],
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'documents'     => $this->handleDocumentsUpload($data['documents'] ?? []),
            ];

            $freelancer = $this->freelancerRepository->create($freelancerData);

            $user->assignRole('freelancer');

            return $freelancer->load(['user', 'profession', 'documents']);
        });
    }
    public function updateFreelancer($freelancer, array $data)
    {
        return DB::transaction(function () use ($freelancer, $data) {

            if (!empty($data['documents'])) {
                $data['documents'] = $this->handleDocumentsUpload($data['documents']);
            }
            $this->freelancerRepository->update($freelancer, $data);

            return $freelancer->fresh(['user', 'profession', 'documents']);
        });
    }

    public function getDetails(int $id)
    {
        return $this->freelancerRepository->getDetails($id);
    }
    public function getRequests(User $user)
    {
        return $this->freelancerRepository->getRequests($user);
    }
    public function requestUpgrade(User $user)
    {
        $upgradeRequest = $this->freelancerRepository->createRequestUpgrade($user);
        Mail::to($user->email)->send(new ConfirmUpgradeMail($upgradeRequest->token));
        return $upgradeRequest;
    }

    public function confirmUpgrade(array $data)
    {
        return DB::transaction(function () use ($data) {
            $request = $this->freelancerRepository->getRequestUserByToken($data['token']);
            if (!$request) {
                throw new HttpException(400, 'invalid token');
            }
            $user = $request->user;

            $freelancerData = [
                'user_id' => $user->id,
                'profession_id' => $data['profession_id'],
                'national_id' => $data['national_id'],
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'documents' => $data['documents'] ?? [],
            ];

            if (!empty($freelancerData['documents'])) {
                $freelancerData['documents'] = array_map(function ($doc) {
                    return FileManager::upload('freelancer_documents', $doc);
                }, $freelancerData['documents']);
            }

            $freelancer = $this->freelancerRepository->create($freelancerData);

            $user->roles()->detach();
            $user->assignRole('freelancer');
            $request->update(['is_used' => true]);
            return $freelancer->load(['user', 'profession', 'documents']);
        });
    }

    public function handleDocumentsUpload(array $documents = []): array
    {
        if (empty($documents)) {
            return [];
        }

        return array_map(function ($doc) {
            return FileManager::upload('freelancer_documents', $doc);
        }, $documents);
    }
}
