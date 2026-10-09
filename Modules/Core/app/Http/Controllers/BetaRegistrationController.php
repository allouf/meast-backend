<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Http\Requests\BetaRegistrationRequest;
use Modules\Core\Services\BetaRegistrationService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BetaRegistrationController extends Controller
{
    public function __construct(protected BetaRegistrationService $service) {}

    public function register(BetaRegistrationRequest $request)
    {
        try {
            $data = $request->validated();
            $user = $this->service->register($data);
            return $this->successResponse(
                'Beta registration successful. Welcome email has been sent.',
                ['user_id' => $user->id, 'email' => $user->email]
            );
        } catch (ValidationException $e) {
            return $this->exceptionResponse($e);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred during beta registration.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadExcel()
    {
        try {
            $filePath = 'beta-registrations.xlsx';
            $disk = Storage::disk('local');
            if (! $disk->exists($filePath)) {
                return $this->errorResponse('Excel file not found. No registrations have been submitted yet.', Response::HTTP_NOT_FOUND);
            }
            $fileName = 'beta-registrations_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            return $disk->download($filePath, $fileName);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred while downloading the Excel file.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
