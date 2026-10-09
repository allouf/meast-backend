<?php

namespace App\Http\Controllers\Admin\User;

use Illuminate\Http\Request;
use Modules\Core\Services\UserService;
use Illuminate\Http\Response;
use Modules\Core\Exports\CompanyExport;
use Modules\Core\Exports\ConsumerExport;
use Modules\Core\Services\CompanyService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\Admin\User\CompanyResource;
use App\Http\Resources\Admin\User\CompanyListResource;
use App\Http\Resources\Admin\User\ConsumerListResource;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Resources\Admin\User\CompanyDetailResource;
use App\Http\Requests\Admin\User\StoreCompanyRequest;
use App\Http\Resources\Admin\User\ConsumerDetailsResource;
use App\Http\Requests\Admin\User\StoreConsumerRequest;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected CompanyService $companyService
    ) {}

    /* ---------------------------- GET ALL CONSUMERS ---------------------------- */
    public function getConsumers(Request $request)
    {
        $consumers = $this->userService->getConsumers($request);
        return $this->successResponse(__('messages.success'), [
            'data' => ConsumerListResource::collection($consumers['data']),
            'meta' => $consumers['meta']
        ]);
    }

    /* ---------------------------- GET CONSUMER DETAILS ---------------------------- */
    public function getConsumerDetails($id)
    {
        $consumer = $this->userService->findById($id);
        return $this->successResponse(__('messages.success'), new ConsumerDetailsResource($consumer));
    }

    /* ---------------------------- GET COMPANIES ---------------------------- */
    public function getCompanies(Request $request)
    {
        $companies = $this->companyService->all($request);
        return $this->successResponse(__('messages.success'), [
            'data' => CompanyListResource::collection($companies['data']),
            'meta' => $companies['meta']
        ]);
    }

    /* ---------------------------- GET COMPANY DETAILS ---------------------------- */
    public function getCompanyDetails($id)
    {
        $user = $this->userService->findById((int) $id);
        if (!$user || !$user->company) {
            return $this->errorResponse(__('messages.user_not_found'), Response::HTTP_NOT_FOUND);
        }
        $user->load('company');
        return $this->successResponse(__('messages.success'), new CompanyDetailResource($user->company));
    }

    /* ---------------------------- CREATE CONSUMER ---------------------------- */

    public function createConsumer(StoreConsumerRequest $request)
    {
        $validated = $request->validated();
        $consumer = $this->userService->createConsumer($validated);

        return $this->successResponse(__('messages.admin.consumer_created_successfully'), new ConsumerDetailsResource($consumer));
    }
    /* ---------------------------- CREATE COMPANY ---------------------------- */

    public function createCompany(StoreCompanyRequest $request)
    {
        $validated = $request->validated();
        $company = $this->companyService->createCompany($validated);

        return $this->successResponse(__('messages.admin.company_created_successfully'), new CompanyDetailResource($company));
    }


    /* ---------------------------- UPDATE USER PROFILE ---------------------------- */
    public function update(UpdateUserRequest $request, $id)
    {
        
        $user = $this->userService->findById($id);
        $updated = $this->userService->updateProfile($user, $request->validated());

        $resourceClass = match (true) {
            $user->hasRole('consumer') => ConsumerDetailsResource::class,
            $user->hasRole('company') && $user->company?->id => CompanyDetailResource::class,
            $user->hasRole('company') => CompanyDetailResource::class,
            default => ConsumerDetailsResource::class,
        };

        $resourceData = ($user->hasRole('company') && $user->company?->id)
            ? $updated->company
            : $updated;

        return $this->successResponse(__('messages.admin.user_updated_successfully'), new $resourceClass($resourceData));
    }


    /* ---------------------------- UPDATE USER ROLE ---------------------------- */
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:consumer,company,admin'
        ]);

        $user = $this->userService->findById($id);
        $this->userService->updateRole($user, $request->role);

        return $this->successResponse(__('messages.admin.user_role_updated_successfully'));
    }

    /* ---------------------------- DELETE USER ---------------------------- */
    public function destroy($id)
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->errorResponse(__('messages.user_not_found'), Response::HTTP_NOT_FOUND);
        }
        if ($user->deletion_reason) {
            return $this->errorResponse(__('messages.admin.user_already_deleted'), Response::HTTP_BAD_REQUEST);
        }
        $this->anonymizeUser($user, $data['reason'] ?? null);

        return $this->successResponse(__('messages.admin.user_deleted_successfully'));
    }
    private function anonymizeUser($user)
    {
        $deletedAt = now();
        $anonymizedEmail = 'deleted_' . $deletedAt->timestamp . $user->email;
        $anonymizedPhone = 'deleted_' . $deletedAt->timestamp . $user->phone;
        $anonymizedName = 'Deleted User ' . $user->name ?? 'User ' . $user->id;

        $user->update([
            'name' => $anonymizedName,
            'email' => $anonymizedEmail,
            'phone' => $anonymizedPhone,
            'verified_at' => null,
            'is_active' => false,
            'deletion_reason' => 'Deleted by admin' . Auth::guard('admin')->user()->name,
        ]);
        if ($user->company) {
            $user->company->update([
                'company_name' => 'Deleted Company ' . $user->company->company_name,
            ]);
        }
    }
    /* ---------------------------- TOGGLE USER ACTIVE STATUS ---------------------------- */
    public function toggleActive($id)
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->errorResponse(__('messages.user_not_found'), Response::HTTP_NOT_FOUND);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $this->successResponse(__('messages.admin.user_status_updated'), ['is_active' => $user->is_active]);
    }
    // ---------------------------- EXPORT CONSUMERS ----------------------------
    public function exportConsumers()
    {
        $fileName = 'consumers_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new ConsumerExport(), $fileName);
    }

    // ---------------------------- EXPORT COMPANIES -----------------------------
    public function exportCompanies()
    {
        $fileName = 'companies_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CompanyExport(), $fileName);
    }
}
