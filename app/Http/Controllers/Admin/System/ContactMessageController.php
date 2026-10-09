<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\Account\ContactMessageRequest;
use App\Http\Resources\Admin\Settings\ContactMessageResource;
use App\Services\ContactMessageService;
use Exception;

class ContactMessageController extends Controller
{
    protected $contactMessageService;
    public function __construct(ContactMessageService $contactMessageService)
    {
        $this->contactMessageService = $contactMessageService;
    }
    public function getContacMessages(){
        try {
            $messages = $this->contactMessageService->all();
            return $this->successResponse(__('fetch_success'),[
                'messages' =>ContactMessageResource::collection($messages['data']),
                'meta' =>$messages['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
           $this->contactMessageService->delete($id);
           return $this->successResponse(__('delete_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function filter(Request $request)
    {
        try {
            $messages = $this->contactMessageService->filter($request->all());
            return $this->successResponse(__('fetch_success'),[
                'messages' =>ContactMessageResource::collection($messages['data']),
                'meta' =>$messages['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
