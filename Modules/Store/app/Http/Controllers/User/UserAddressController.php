<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\AddUserAddressRequest;
use Modules\Store\Http\Requests\User\UpdateUserAddressRequest;
use Modules\Store\Http\Resources\User\UserAddressResource;
use Modules\Store\Services\UserAddressService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    protected $userAddressService;

    public function __construct(UserAddressService $userAddressService)
    {
        $this->userAddressService = $userAddressService;
    }

    /**
     * Get my addresses
     */
    public function getMyAddresses()
    {
        try {
            $userId = Auth::id();
            $addresses = $this->userAddressService->getMyAddresses($userId);

            return $this->successResponse('Addresses retrieved successfully', UserAddressResource::collection($addresses));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Add address
     */
    public function addAddress(AddUserAddressRequest $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $address = $this->userAddressService->addAddress($userId, $data);

            return $this->successResponse('Address added successfully', new UserAddressResource($address));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update address
     */
    public function updateAddress(UpdateUserAddressRequest $request, $addressId)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $address = $this->userAddressService->updateAddress($userId, $addressId, $data);

            return $this->successResponse('Address updated successfully', new UserAddressResource($address));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Remove address
     */
    public function removeAddress($addressId)
    {
        try {
            $userId = Auth::id();

            $deleted = $this->userAddressService->removeAddress($userId, $addressId);

            if ($deleted) {
                return $this->successResponse('Address removed successfully');
            }

            return $this->errorResponse('Failed to remove address', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

