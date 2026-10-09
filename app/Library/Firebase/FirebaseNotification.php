<?php

namespace App\Library\Firebase;

use App\Models\FcmToken;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class FirebaseNotification
{
    private array $title = [];
    private array $body = [];
    private ?string $icon = null;
    private ?string $topic = null;
    private array $tokens = [];
    private Collection $users;
    private array $data = [];
    private string $projectId;
    private string $credentialsFilePath;

    public function __construct(string $projectId, string $credentialsFilePath)
    {
        $this->projectId = $projectId;
        $this->credentialsFilePath = $credentialsFilePath;
    }

    /**
     * Set titles for multiple languages
     */
    public function setTitle(array $title): self
    {
        $this->title = $title; // ['en' => '...', 'ar' => '...']
        return $this;
    }

    /**
     * Set bodies for multiple languages
     */
    public function setBody(array $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function setUsers(Collection $users): self
    {
        $this->users = $users;

        $this->tokens = FcmToken::where(function ($query) use ($users) {
            foreach ($users as $user) {
                $query->orWhere(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
        })->pluck('token')->toArray();

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    private function buildPayload(string $token): array
    {
        $notification = [
            'title' => $this->title['en'] ?? '',
            'body' => $this->body['en'] ?? '',
            'image' => $this->icon ?? '',
        ];

        $data = [
            'title_en' => $this->title['en'] ?? '',
            'body_en' => $this->body['en'] ?? '',
            'title_ar' => $this->title['ar'] ?? '',
            'body_ar' => $this->body['ar'] ?? '',
            'icon' => $this->icon ?? '',
        ];

        if (!empty($this->data)) {
            $data['additional_data'] = json_encode($this->data);
        }

        $message = [
            'notification' => $notification,
            'data' => $data,
            'android' => [
                'notification' => [
                    'image' => $this->icon ?? ''
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'mutable-content' => 1
                    ]
                ],
                'fcm_options' => [
                    'image' => $this->icon ?? ''
                ]
            ],
        ];

        if ($this->topic) {
            $message['topic'] = $this->topic;
        } else {
            $message['token'] = $token;
        }

        return ['message' => $message];
    }


    private function send(array $payload): bool
    {
        $apiUrl = 'https://fcm.googleapis.com/v1/projects/' . config('firebase.project_id') . '/messages:send';
        $accessToken = AccessToken::getToken();

        $response = Http::withHeaders([
            "Authorization" => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ])->post($apiUrl, $payload);

        if ($response->failed()) {
            Log::error('Firebase notification failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload
            ]);
            return false;
        }

        return true;
    }

    public function push(): bool
    {
        AccessToken::initialize($this->credentialsFilePath, $this->projectId);

        foreach ($this->tokens as $token) {
            $payload = $this->buildPayload($token);
            if (!$this->send($payload)) {
                return false;
            }
        }

        $this->saveNotification($this->topic !== null);
        return true;
    }

    private function saveNotification(bool $isGeneral): void
    {
        $notification = new Notification();

        $notification->setTranslations('title', $this->title);
        $notification->setTranslations('body', $this->body);

        $notification->type = $isGeneral ? 'general' : 'system';
        $notification->save();

        if (!$isGeneral && $this->users->isNotEmpty()) {
            $userNotifications = [];

            foreach ($this->users as $user) {
                $userNotifications[] = [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                ];
            }

            UserNotification::insert($userNotifications);
        }
    }
}
