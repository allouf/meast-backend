<?php

namespace App\Repositories\Eloquent;

use App\Models\AccountUpgrade;
use App\Repositories\Interfaces\FreelancerRepositoryInterface;
use App\Models\Freelancer;
use App\Utilities\FileManager;
use Illuminate\Support\Str;
use App\Models\User;
use App\Traits\PaginateTrait;

class FreelancerRepository implements FreelancerRepositoryInterface
{
    use PaginateTrait;
    protected $model;

    public function __construct(Freelancer $model)
    {
        $this->model = $model;
    }
    public function all()
    {
        $query = $this->model->with('user', 'profession')->orderBy('id', 'desc');
        return $this->paginate($query);
    }
    public function create(array $data)
    {
        $freelancer = $this->model->create([
            'user_id'       => $data['user_id'],
            'profession_id' => $data['profession_id'],
            'national_id'   => $data['national_id'],
            'portfolio_url' => $data['portfolio_url'] ?? null,
        ]);

        if (!empty($data['documents'])) {
            foreach ($data['documents'] as $filePath) {
                $freelancer->documents()->create([
                    'file_path' => $filePath,
                    'file_type' => FileManager::getFileTypeFromPath($filePath),
                ]);
            }
        }

        return $freelancer;
    }
    public function update(Freelancer $freelancer, array $data)
    {
        $freelancer->update([
            'profession_id' => $data['profession_id'] ?? $freelancer->profession_id,
            'national_id'   => $data['national_id'] ?? $freelancer->national_id,
            'portfolio_url' => $data['portfolio_url'] ?? $freelancer->portfolio_url,
        ]);

        if (!empty($data['documents'])) {
            foreach ($data['documents'] as $filePath) {
                $freelancer->documents()->create([
                    'file_path' => $filePath,
                    'file_type' => FileManager::getFileTypeFromPath($filePath),
                ]);
            }
        }

        return $freelancer->fresh(['documents']);
    }

    public function getDetails(int $id)
    {
        $freelancer = $this->model->findOrFail($id);
        $freelancer->load('user', 'profession', 'documents');
        return $freelancer;
    }

    public function getRequests(User $user)
    {
        return AccountUpgrade::with('user')->where('user_id', $user->id)->get();
    }
    public function createRequestUpgrade(User $user)
    {
        $existing = AccountUpgrade::where('user_id', $user->id)
            ->where('is_used', false)
            ->first();

        if ($existing) {
            return $existing;
        }

        $token = Str::uuid()->toString();
        return AccountUpgrade::create([
            'user_id' => $user->id,
            'token'   => $token,
            'is_used' => false,
        ]);
    }

    public function getRequestUserByToken(string $token)
    {
        $request = AccountUpgrade::where('token', $token)
            ->where('is_used', false)
            ->firstOrFail();
        $request->load('user');
        return $request;
    }
}
