<?php

namespace App\Repositories\Eloquent;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\ChannelCallDetail;
use App\Models\ChannelMemberRequest;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Auth;

class ChannelRepository implements ChannelRepositoryInterface
{
    public function createChannel(array $data)
    {
        $channel = Channel::create($data);

        $this->addMember($channel->id, $data['owner_id'], 'owner');

        return $channel;
    }

    public function updateChannel(array $data, $channelId)
    {
        $channel = $this->getChannelById($channelId);
        $channel->update($data);
        return $channel;
    }

    public function deleteChannel($channelId)
    {
        $channel = $this->getChannelById($channelId);
        return $channel->delete();
    }

    public function getChannelById($channelId)
    {
        $channel = Channel::with(['owner', 'messages'])->find($channelId);
        if (!$channel) {
            throw new NotFoundHttpException('Channel not found');
        }
        return $channel;
    }

    public function getChannelsByUser($userId)
    {
        return Channel::whereHas('channelMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'active');
        })->with(['owner', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])->orderBy('updated_at', 'desc')->get();
    }

    public function getPublicChannels()
    {
        return Channel::where('privacy', 'public')
            ->where('is_active', true)
            ->with(['owner'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function addMember($channelId, $userId, $role = 'member')
    {
        return ChannelMember::create([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now()
        ]);
    }

    public function removeMember($channelId, $userId)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function updateMemberRole($channelId, $userId, $role)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->update(['role' => $role]);
    }

    public function updateMemberStatus($channelId, $userId, $status)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->update(['status' => $status]);
    }

    public function isMember($channelId, $userId)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function isOwner($channelId, $userId)
    {
        return Channel::where('id', $channelId)
            ->where('owner_id', $userId)
            ->exists();
    }

    public function isAdmin($channelId, $userId)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function canSendMessage($channelId, $userId)
    {
        $channel = $this->getChannelById($channelId);
        return $channel->canSendMessage($userId);
    }

    public function canUploadMedia($channelId, $userId)
    {
        $channel = $this->getChannelById($channelId);
        return $channel->canUploadMedia($userId);
    }

    public function canMakeCall($channelId, $userId)
    {
        $channel = $this->getChannelById($channelId);
        return $channel->canMakeCall($userId);
    }

    public function sendMessage(array $data)
    {
        $message = ChannelMessage::create($data);

        ChannelMember::where('channel_id', $data['channel_id'])
            ->where('user_id', $data['sender_id'])
            ->update(['last_read_at' => now()]);

        return $message;
    }

    public function getMessagesByChannel($channelId, $userId)
    {
        $channel = $this->getChannelById($channelId);

        $this->markMessagesAsRead($channelId, $userId);

        $messages = ChannelMessage::where('channel_id', $channelId)
            ->with(['sender', 'replyTo'])
            ->orderBy('created_at', 'asc')
            ->get();

        return $messages->groupBy(function ($message) {
            return $message->created_at->format('Y-m-d');
        });
    }

    public function markMessagesAsRead($channelId, $userId)
    {
        $unreadMessages = ChannelMessage::where('channel_id', $channelId)
            ->where('sender_id', '!=', $userId)
            ->whereJsonDoesntContain('read_by', $userId)
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsRead($userId);
        }

        ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    public function createCallDetail(array $data)
    {
        return ChannelCallDetail::create($data);
    }

    public function updateCallDetail($callDetailId)
    {
        $callDetail = ChannelCallDetail::findOrFail($callDetailId);
        $callDetail->endCall();
        return $callDetail;
    }

    public function getActiveCall($channelId)
    {
        return ChannelCallDetail::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();
    }

    public function getChannelMembers($channelId)
    {
        return ChannelMember::where('channel_id', $channelId)
            ->with('user')
            ->orderBy('role', 'desc')
            ->orderBy('joined_at', 'asc')
            ->get();
    }

    public function searchChannels($query)
    {
        return Channel::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->where('is_active', true)
            ->with(['owner', 'channelMembers'])
            ->get();
    }

    public function getChannelStats($channelId)
    {
        $channel = $this->getChannelById($channelId);

        return [
            'total_members' => $channel->channelMembers()->count(),
            'total_messages' => $channel->messages()->count(),
            'active_members' => $channel->channelMembers()->where('is_online', true)->count(),
            'unread_messages' => $channel->getUnreadCount(Auth::id())
        ];
    }

    public function createMemberRequest($channelId, $userId, $message = null)
    {
        return ChannelMemberRequest::create([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'message' => $message,
            'status' => 'pending'
        ]);
    }

    public function getMemberRequestsByChannel($channelId)
    {
        return ChannelMemberRequest::where('channel_id', $channelId)
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getMemberRequestsByUser($userId)
    {
        return ChannelMemberRequest::where('user_id', $userId)
            ->with(['channel', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function approveMemberRequest($requestId, $reviewerId)
    {
        $request = ChannelMemberRequest::findOrFail($requestId);

        $this->addMember($request->channel_id, $request->user_id);

        $request->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now()
        ]);

        return $request;
    }

    public function rejectMemberRequest($requestId, $reviewerId)
    {
        $request = ChannelMemberRequest::findOrFail($requestId);

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now()
        ]);

        return $request;
    }

    public function hasPendingRequest($channelId, $userId)
    {
        return ChannelMemberRequest::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }
}
