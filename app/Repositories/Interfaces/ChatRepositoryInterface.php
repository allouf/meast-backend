<?php

namespace App\Repositories\Interfaces;

interface ChatRepositoryInterface
{
    public function findExistingConversation($authUserId, $receiverId);
    public function createConversation($authUserId, $receiverId);
    public function sendMessage(array $data);
    public function getMessagesByConversation($conversationId,$authUserId);
    public function updateStatus($data,$conversationId);
    public function getConversations($authUserId);
    public function createCallDetail(array $data);
    public function updateCallDetail($callDetailId);
}
