<?php

namespace Modules\Services\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoryVerificationRequiredException extends HttpException
{
    public const ERROR_CODE = 'VERIFICATION_REQUIRED';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            403,
            $message ?? __('services::messages.service_provider.verification_required')
        );
    }
}
