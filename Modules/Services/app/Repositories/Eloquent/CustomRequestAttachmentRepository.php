<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\Attachment;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Repositories\Interfaces\CustomRequestAttachmentRepositoryInterface;

class CustomRequestAttachmentRepository implements CustomRequestAttachmentRepositoryInterface
{
    public function createForCustomRequest(CustomRequest $customRequest, array $attributes): Attachment
    {
        return $customRequest->attachments()->create($attributes);
    }
}
