<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\CallDetail;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ChatRepository implements ChatRepositoryInterface
{
    public function findExistingConversation($authUserId, $receiverId)
    {
        $conversation = Conversation::where(function ($query) use ($authUserId, $receiverId) {
            $query->where('initiator_id', $authUserId)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($authUserId, $receiverId) {
            $query->where('initiator_id', $receiverId)
                ->where('receiver_id', $authUserId);
        })->with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->first();
        if ($conversation) {
            $this->markMessagesAsRead($conversation->id, $authUserId);
            $conversation->messages = $conversation->messages->groupBy(function ($message) {
                return $message->created_at->format('Y-m-d');
            });
        }
        return $conversation;
    }
    public function createConversation($authUserId, $receiverId)
    {
        $receiver = User::find($receiverId);
        return Conversation::create([
            'initiator_id' => $authUserId,
            'receiver_id' => $receiverId,
            'name' => $receiver->name,
            'status_by_initiator' => 'unread',
            'status_by_receiver' => 'unread',
        ]);
    }
    public function sendMessage(array $data)
    {
        $message = Message::create($data);
        $conversation = Conversation::find($message->conversation_id);
        if ($conversation) {
            $statusColumn = ($message->sender_id == $conversation->initiator_id)
                ? 'status_by_initiator'
                : 'status_by_receiver';
            $conversation->update([
                $statusColumn => 'unread',
            ]);
        }
        return $message;
    }
    public function getMessagesByConversation($conversationId, $authUserId)
    {
        $this->markMessagesAsRead($conversationId, $authUserId);
        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('id', 'asc')
            ->get();
        return $messages->groupBy(function ($message) {
            return $message->created_at->format('Y-m-d');
        });
    }
    public function getConversations($authUserId)
    {
        return Conversation::with('messages')
            ->where('initiator_id', $authUserId)
            ->orWhere('receiver_id', $authUserId)
            ->orderBy('updated_at', 'desc')
            ->withCount([
                'messages as unread_message_count' => function ($query) use ($authUserId) {
                    $query->where('sender_id', '!=', $authUserId)
                        ->where('is_read', false);
                }
            ])
            ->get();
    }
    public function updateStatus($data, $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        $userId = Auth::id();
        if ($conversation) {
            if ($userId == $conversation->initiator_id) {
                return $conversation->update([
                    'status_by_initiator' => $data['status'] ?? $conversation->status_by_initiator,
                ]);
            }
            if ($userId == $conversation->receiver_id) {
                return $conversation->update([
                    'status_by_receiver' => $data['status'] ?? $conversation->status_by_receiver,
                ]);
            }
        }
    }
    public function markMessagesAsRead($conversationId, $authUserId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $authUserId)
            ->where('is_read', false)
            ->get();
        if ($messages->isNotEmpty()) {
            Message::where('conversation_id', $conversationId)
                ->where('sender_id', '!=', $authUserId)

                ->where('is_read', false)
                ->update(['is_read' => true]);
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                $statusField = ($conversation->initiator_id === $authUserId)
                    ? 'status_by_initiator'
                    : 'status_by_receiver';
                $conversation->update([$statusField => 'read']);
            }
        }
    }
    public function createCallDetail(array $data)
{
    $callDetail = CallDetail::create([
        'conversation_id' => $data['conversation_id'],
        'start_call_date' => now(),
        'call_type' => $data['call_type']
    ]);
    return $callDetail;
}

    public function updateCallDetail($callDetailId)
{
    return CallDetail::where('id', $callDetailId)
        ->update(['end_call_date' => now()]);
}
}
