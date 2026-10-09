<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface ReferralRepositoryInterface
{
       // Referral Rules
       public function getAllRules();
       public function findRuleById($id);
       public function createRule(array $data);
       public function updateRule($id, array $data);
       public function deleteRule($id);

       // Referral Milestones
       public function getAllMilestones();
       public function findMilestoneById($id);
       public function createMilestone(array $data);
       public function updateMilestone($id, array $data);
       public function deleteMilestone($id);


       // Handle Referral

       public function createReferralLog(array $data);
       public function incrementUserPoints(int $userId, int $points);
       public function createPointTransaction(int $userId, int $points, string $reason);
       public function updateReferredBy(int $userId, int $referrerId);
       public function getUserPoints(int $referrerId);
       public function userHasMilestoneReward(int $userId, int $milestoneId): bool;
       public function decrementUserPoints(int $userId, int $points);
       public function getMilestonesByPoints(int $points);
       public function createUserReward(int $userId, int $milestoneId, bool $redeemed = false);
       public function markAsRedeemed(int $rewardId);
}
