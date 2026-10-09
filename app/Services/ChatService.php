<?php

namespace App\Services;

use App\Utilities\FileManager;
use App\Events\NewMessageEvent;
use App\Events\MessageReadEvent;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\Chat\MessageResource;
use App\Repositories\Interfaces\ChatRepositoryInterface;

class ChatService
{
    protected $chatRepository;
    public function __construct(ChatRepositoryInterface $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }
    public function startConversation($receiverId)
    {
        $authUserId = Auth::id();
        //hi
        $conversation = $this->chatRepository->findExistingConversation($authUserId, $receiverId);
        if (!$conversation) {
            $conversation = $this->chatRepository->createConversation($authUserId, $receiverId);
        }
        event(new MessageReadEvent($conversation->id, $authUserId));
        return $conversation;
    }
    public function sendMessage($data)
    {
        $data['sender_id'] = Auth::id();
        if ($data['message_type'] != 'text' && isset($data['message'])) {
            $data['message'] = FileManager::upload('chat', $data['message']);
        } else {
            $data['message'] = $data['message'];
        }
        $message = $this->chatRepository->sendMessage($data);
        event(new NewMessageEvent($message));
        return $message;
    }
    public function getMessagesByConversation($conversationId, $authUserId)
    {
        event(new MessageReadEvent($conversationId, $authUserId));
        $groupedMessages = $this->chatRepository->getMessagesByConversation($conversationId, $authUserId);
        return $this->formatGroupedMessages($groupedMessages);
    }
    private function formatGroupedMessages($groupedMessages)
    {
        return $groupedMessages->map(function ($messages, $date) {
            return [
                'date' => $date,
                'messages' => MessageResource::collection($messages),
            ];
        })->values()->toArray();
    }
    public function getConversations($authUserId)
    {
        return $this->chatRepository->getConversations($authUserId);
    }
    public function updateStatus($data, $conversationId)
    {
        return $this->chatRepository->updateStatus($data, $conversationId);
    }

    public function createCallDetail(array $data)
{
    return $this->chatRepository->createCallDetail($data);
}

public function updateCallDetail($callDetailId)
{
    return $this->chatRepository->updateCallDetail($callDetailId);
}
}
