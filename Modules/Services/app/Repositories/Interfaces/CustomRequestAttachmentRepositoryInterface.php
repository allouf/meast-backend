<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\Attachment;
use Modules\Services\Models\CustomRequest;

interface CustomRequestAttachmentRepositoryInterface
{
    public function createForCustomRequest(CustomRequest $customRequest, array $attributes): Attachment;
}
