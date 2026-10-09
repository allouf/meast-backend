<?php

namespace App\Repositories\Interfaces;

interface ChannelRepositoryInterface
{
    public function createChannel(array $data);
    public function updateChannel(array $data, $channelId);
    public function deleteChannel($channelId);
    public function getChannelById($channelId);
    public function getChannelsByUser($userId);
    public function getPublicChannels();
    public function addMember($channelId, $userId, $role = 'member');
    public function removeMember($channelId, $userId);
    public function updateMemberRole($channelId, $userId, $role);
    public function updateMemberStatus($channelId, $userId, $status);
    public function isMember($channelId, $userId);
    public function isOwner($channelId, $userId);
    public function isAdmin($channelId, $userId);
    public function canSendMessage($channelId, $userId);
    public function canUploadMedia($channelId, $userId);
    public function canMakeCall($channelId, $userId);
    public function sendMessage(array $data);
    public function getMessagesByChannel($channelId, $userId);
    public function markMessagesAsRead($channelId, $userId);
    public function createCallDetail(array $data);
    public function updateCallDetail($callDetailId);
    public function getActiveCall($channelId);
    public function getChannelMembers($channelId);
    public function searchChannels($query);
    public function getChannelStats($channelId);

    // Member request methods
    public function createMemberRequest($channelId, $userId, $message = null);
    public function getMemberRequestsByChannel($channelId);
    public function getMemberRequestsByUser($userId);
    public function approveMemberRequest($requestId, $reviewerId);
    public function rejectMemberRequest($requestId, $reviewerId);
    public function hasPendingRequest($channelId, $userId);
}
