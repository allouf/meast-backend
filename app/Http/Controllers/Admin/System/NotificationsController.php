<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Library\Firebase\FirebaseNotification;
use App\Models\User;
use Illuminate\Support\Facades\App;
use App\Http\Requests\Admin\Notifications\NotificationRequest;
use Exception;

class NotificationsController extends Controller
{


public function send(NotificationRequest $request)
{
    try {
        $users = User::whereIn('id', $request->user_ids)->get();

        $notification = new FirebaseNotification(
            config('firebase.project_id'),
            config('firebase.credentials_file_path')
        );

        $notification->setTitle($request->title)
            ->setBody($request->body)
            ->setUsers($users);

        if ($request->filled('icon')) {
            $notification->setIcon($request->icon);
        }

        if ($request->filled('data')) {
            $notification->setData($request->data);
        }

        $notification->push();

        return $this->successResponse(__('notification_sent_successfully'));
    } catch (Exception $e) {
        return $this->exceptionResponse($e);
    }
}
}
