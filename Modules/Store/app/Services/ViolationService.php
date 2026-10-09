<?php

namespace Modules\Store\Services;

use Modules\Store\Enums\ViolationStatus;
use Modules\Store\Models\Violation;
use Modules\Store\Repositories\Interfaces\ViolationRepositoryInterface;
use App\Utilities\FileManager;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ViolationService
{
    protected $violationRepository;
    public function __construct(ViolationRepositoryInterface $violationRepository)
    {
        $this->violationRepository = $violationRepository;
    }
    public function all(array $filters)
    {
        return $this->violationRepository->all($filters);
    }
    public function findById($id)
    {
        return $this->violationRepository->findById($id);
    }
    public function create(array $data)
    {
        // Map polymorphic target
        if (!empty($data['type']) && !empty($data['type_id'])) {
            $data['violatable_type'] = match (strtolower($data['type'])) {
                'store'   => \Modules\Store\Models\Store::class,
                'product' => \Modules\Store\Models\Product::class,
                'post'    => \Modules\Community\Models\Post::class,
                'comment' => \Modules\Community\Models\Comment::class,
                default   => throw new HttpException(400, "Unknown type: {$data['type']}"),
            };

            $data['violatable_id'] = $data['type_id'];
        }

        // Handle files upload
        $uploadedFiles = [];
        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $uploadedFiles[] = [
                    'file_path' => FileManager::upload('violations', $file),
                    'type'      => FileManager::getFileType($file),
                ];
            }
        }

        // Cleanup request-only fields
        unset($data['type'], $data['type_id'], $data['files']);

        // Create violation (reason_id موجود وجاهز)
        $violation = $this->violationRepository->create($data);

        // Attach files
        if (!empty($uploadedFiles)) {
            $violation->files()->createMany($uploadedFiles);
        }

        return $violation;
    }

    public function update(array $data, $id)
    {
        $violation = $this->violationRepository->findById($id);
        throw_if(!$violation, ModelNotFoundException::class, "Record not found");
        return $this->violationRepository->update($data, $violation);
    }
    public function delete($id)
    {
        $violation = $this->violationRepository->findById($id);
        throw_if(!$violation, ModelNotFoundException::class, "Record not found");
        return $this->violationRepository->delete($violation);
    }

    public function takeAction(int $id, ViolationStatus $status, ?string $adminNote = null): Violation
    {
        $violation = $this->violationRepository->findById($id);

        $currentStatus = $violation->status;

        $allowedTransitions = [
            ViolationStatus::PENDING->value  => [ViolationStatus::REVIEWED, ViolationStatus::IGNORED],
            ViolationStatus::REVIEWED->value => [ViolationStatus::ACTION_TAKEN, ViolationStatus::IGNORED],
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            throw new \DomainException('This violation has already been processed.');
        }

        if (!in_array($status, $allowedTransitions[$currentStatus], true)) {
            throw new \DomainException("Cannot change status from '{$currentStatus}' to '{$status->value}'.");
        }

        $violation->update([
            'status'    => $status->value,
            'admin_id'  => auth()->id(),
            'admin_note'=> $adminNote,
        ]);

        return $violation->load(['user', 'admin', 'reason', 'files', 'violatable']);
    }

}
