<?php

namespace App\Services;

use App\Utilities\FileManager;
use App\Events\ChannelMessageEvent;
use App\Events\ChannelCallEvent;
use App\Events\ChannelMemberEvent;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChannelService
{
    protected $channelRepository;

    public function __construct(ChannelRepositoryInterface $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    public function createChannel($data)
    {
        $data['owner_id'] = Auth::id();

        if (isset($data['avatar'])) {
            $data['avatar'] = FileManager::upload('channels', $data['avatar']);
        }

        $channel = $this->channelRepository->createChannel($data);

        event(new ChannelMemberEvent($channel->id, Auth::id(), 'joined'));

        return $channel;
    }

    public function updateChannel($data, $channelId)
    {
        if (!$this->channelRepository->isOwner($channelId, Auth::id()) &&
            !$this->channelRepository->isAdmin($channelId, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to update channel');
        }
        $channel = $this->channelRepository->getChannelById($channelId);
        if (!$channel) {
            throw new NotFoundHttpException('Channel not found');
        }

        if (isset($data['avatar'])) {
            $data['avatar'] = FileManager::upload('channels', $data['avatar']);
        }

        return $this->channelRepository->updateChannel($data, $channelId);
    }

    public function deleteChannel($channelId)
    {
        $channel = $this->channelRepository->getChannelById($channelId);
        if (!$channel) {
            throw new NotFoundHttpException('Channel not found');
        }
        if (!$this->channelRepository->isOwner($channelId, Auth::id())) {
            throw new HttpException(403, 'Only channel owner can delete the channel');
        }

        return $this->channelRepository->deleteChannel($channelId);
    }

    public function joinChannel($channelId, $message = null)
    {
        $channel = $this->channelRepository->getChannelById($channelId);

        if ($this->channelRepository->isMember($channelId, Auth::id())) {
            throw new HttpException(400, 'Already a member of this channel');
        }

        if ($channel->channelMembers()->count() >= $channel->max_members) {
            throw new HttpException(400, 'Channel is full');
        }

        if ($channel->privacy === 'private') {
            if ($this->channelRepository->hasPendingRequest($channelId, Auth::id())) {
                throw new HttpException(400, 'You already have a pending request for this channel');
            }

            $request = $this->channelRepository->createMemberRequest($channelId, Auth::id(), $message);

            return [
                'message' => 'Join request submitted successfully. Please wait for approval.',
                'request' => $request
            ];
        }

        $this->channelRepository->addMember($channelId, Auth::id());
        event(new ChannelMemberEvent($channelId, Auth::id(), 'joined'));

        return $channel;
    }

    public function leaveChannel($channelId)
    {
        $channel = $this->channelRepository->getChannelById($channelId);
        if (!$channel) {
            throw new NotFoundHttpException('Channel not found');
        }

        if ($this->channelRepository->isOwner($channelId, Auth::id())) {
            throw new HttpException(400, 'Channel owner cannot leave the channel');
        }

        $this->channelRepository->removeMember($channelId, Auth::id());

        event(new ChannelMemberEvent($channelId, Auth::id(), 'left'));

        return true;
    }

    public function addMember($channelId, $userId, $role = 'member')
    {
        if (!$this->channelRepository->isOwner($channelId, Auth::id()) &&
            !$this->channelRepository->isAdmin($channelId, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to add members');
        }

        $this->channelRepository->addMember($channelId, $userId, $role);

        event(new ChannelMemberEvent($channelId, $userId, 'added'));

        return true;
    }

    public function removeMember($channelId, $userId)
    {
        if (!$this->channelRepository->isOwner($channelId, Auth::id()) &&
            !$this->channelRepository->isAdmin($channelId, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to remove members');
        }

        $this->channelRepository->removeMember($channelId, $userId);

        event(new ChannelMemberEvent($channelId, $userId, 'removed'));

        return true;
    }

    public function sendMessage($data)
    {
        if (!$this->channelRepository->canSendMessage($data['channel_id'], Auth::id())) {
            throw new HttpException(400, 'Unauthorized to send messages in this channel');
        }

        $data['sender_id'] = Auth::id();

        if ($data['message_type'] !== 'text' && isset($data['message'])) {
            $data['message'] = FileManager::upload('channel-messages', $data['message']);
        }

        $message = $this->channelRepository->sendMessage($data);

        event(new ChannelMessageEvent($message));

        return $message;
    }

    public function getMessages($channelId)
    {
        if (!$this->channelRepository->isMember($channelId, Auth::id())) {
            throw new HttpException(400, 'Not a member of this channel');
        }

        $groupedMessages = $this->channelRepository->getMessagesByChannel($channelId, Auth::id());

        return $this->formatGroupedMessages($groupedMessages);
    }

    private function formatGroupedMessages($groupedMessages)
    {
        return $groupedMessages->map(function ($messages, $date) {
            return [
                'date' => $date,
                'messages' => $messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                            'photo' => $message->sender->photo
                        ],
                        'message' => $message->message,
                        'message_type' => $message->message_type,
                        'is_pinned' => $message->is_pinned,
                        'reply_to' => $message->replyTo ? [
                            'id' => $message->replyTo->id,
                            'message' => $message->replyTo->message,
                            'sender_name' => $message->replyTo->sender->name
                        ] : null,
                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                        'is_read' => $message->isReadBy(Auth::id())
                    ];
                })
            ];
        })->values()->toArray();
    }

    public function getChannels()
    {
        return $this->channelRepository->getChannelsByUser(Auth::id());
    }

    public function getPublicChannels()
    {
        $channels = $this->channelRepository->getPublicChannels();
        $userId = Auth::id();

        return $channels->map(function ($channel) use ($userId) {
            return [
                'id' => $channel->id,
                'name' => $channel->name,
                'description' => $channel->description,
                'avatar' => $channel->avatar,
                'privacy' => $channel->privacy,
                'owner' => $channel->owner,
                'created_at' => $channel->created_at,
                'updated_at' => $channel->updated_at,
                'is_owner' => $channel->owner_id == $userId
            ];
        });
    }

    public function getChannelDetails($channelId)
    {
        $userId = Auth::id();

        if (!$this->channelRepository->isMember($channelId, Auth::id())) {
            throw new HttpException(400, 'Not a member of this channel');
        }

        $channel = $this->channelRepository->getChannelById($channelId);
        $stats = $this->channelRepository->getChannelStats($channelId);

        return [
            'channel' => $channel,
            'stats' => $stats,
            'is_owner' => $channel->owner_id == $userId
        ];
    }

    public function getChannelMembers($channelId)
    {
        if (!$this->channelRepository->isMember($channelId, Auth::id())) {
            throw new HttpException(400, 'Not a member of this channel');
        }

        return $this->channelRepository->getChannelMembers($channelId);
    }
    public function startCall($channelId, $callType)
    {
        if (!$this->channelRepository->canMakeCall($channelId, Auth::id())) {
            throw new HttpException(400, 'Unauthorized to make calls in this channel');
        }

        $activeCall = $this->channelRepository->getActiveCall($channelId);
        if ($activeCall) {
            throw new HttpException(400, 'There is already an active call in this channel');
        }

        $callDetail = $this->channelRepository->createCallDetail([
            'channel_id' => $channelId,
            'initiator_id' => Auth::id(),
            'start_call_date' => now(),
            'call_type' => $callType,
            'channel_name' => 'channel_' . $channelId . '_' . time(),
            'participants' => [Auth::id()],
            'is_active' => true
        ]);

        event(new ChannelCallEvent($callDetail, 'started'));

        return $callDetail;
    }

    public function endCall($callDetailId)
    {
        $callDetail = $this->channelRepository->updateCallDetail($callDetailId);

        event(new ChannelCallEvent($callDetail, 'ended'));

        return $callDetail;
    }

    public function searchChannels($query)
    {
        return $this->channelRepository->searchChannels($query);
    }

    public function getMemberRequests($channelId)
    {
        if (!$this->channelRepository->isOwner($channelId, Auth::id()) &&
            !$this->channelRepository->isAdmin($channelId, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to view member requests');
        }

        return $this->channelRepository->getMemberRequestsByChannel($channelId);
    }

    public function getMyMemberRequests()
    {
        return $this->channelRepository->getMemberRequestsByUser(Auth::id());
    }

    public function approveMemberRequest($requestId)
    {
        $request = \App\Models\ChannelMemberRequest::findOrFail($requestId);

        if (!$this->channelRepository->isOwner($request->channel_id, Auth::id()) &&
            !$this->channelRepository->isAdmin($request->channel_id, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to approve member requests');
        }

        if ($request->status !== 'pending') {
            throw new HttpException(400, 'Request has already been processed');
        }

        $approvedRequest = $this->channelRepository->approveMemberRequest($requestId, Auth::id());

        event(new ChannelMemberEvent($request->channel_id, $request->user_id, 'joined'));

        return $approvedRequest;
    }

    public function rejectMemberRequest($requestId)
    {
        $request = \App\Models\ChannelMemberRequest::findOrFail($requestId);

        if (!$this->channelRepository->isOwner($request->channel_id, Auth::id()) &&
            !$this->channelRepository->isAdmin($request->channel_id, Auth::id())) {
            throw new HttpException(403, 'Unauthorized to reject member requests');
        }

        if ($request->status !== 'pending') {
            throw new HttpException(400, 'Request has already been processed');
        }

        return $this->channelRepository->rejectMemberRequest($requestId, Auth::id());
    }
}
