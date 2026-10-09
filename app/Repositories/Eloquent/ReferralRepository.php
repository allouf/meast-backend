<?php

namespace App\Repositories\Eloquent;

use App\Models\PointTransaction;
use App\Models\ReferralLog;
use App\Repositories\Interfaces\ReferralRepositoryInterface;
use App\Models\ReferralRule;
use App\Models\ReferralMilestone;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserReward;
use Illuminate\Support\Facades\DB;

class ReferralRepository implements ReferralRepositoryInterface
{
    protected $ruleModel;
    protected $milestoneModel;

    public function __construct(ReferralRule $ruleModel, ReferralMilestone $milestoneModel)
    {
        $this->ruleModel = $ruleModel;
        $this->milestoneModel = $milestoneModel;
    }

    // ----- Referral Rules -----
    public function getAllRules()
    {
        return $this->ruleModel->all();
    }

    public function findRuleById($id)
    {
        return $this->ruleModel->findOrFail($id);
    }

    public function createRule(array $data)
    {
        return $this->ruleModel->create($data);
    }

    public function updateRule($id, array $data)
    {
        $rule = $this->ruleModel->findOrFail($id);
        $rule->update($data);
        return $rule;
    }

    public function deleteRule($id)
    {
        return $this->ruleModel->destroy($id);
    }

    // ----- Referral Milestones -----
    public function getAllMilestones()
    {
        return $this->milestoneModel->all();
    }

    public function findMilestoneById($id)
    {
        return $this->milestoneModel->findOrFail($id);
    }

    public function createMilestone(array $data)
    {
        return $this->milestoneModel->create($data);
    }

    public function updateMilestone($id, array $data)
    {
        $milestone = $this->milestoneModel->findOrFail($id);
        $milestone->update($data);
        return $milestone;
    }

    public function deleteMilestone($id)
    {
        return $this->milestoneModel->destroy($id);
    }

    // -----Handle Referral -----
    public function createReferralLog(array $data)
    {
        return ReferralLog::create($data);
    }
    public function incrementUserPoints(int $userId, int $points)
    {
        $userPoint = UserPoint::firstOrNew(['user_id' => $userId]);

        $userPoint->points = ($userPoint->points ?? 0) + $points;
        $userPoint->save();
    }


    public function createPointTransaction(int $userId, int $points, string $reason)
    {
        return PointTransaction::create([
            'user_id' => $userId,
            'points' => $points,
            'reason' => $reason,
        ]);
    }

    public function updateReferredBy(int $userId, int $referrerId)
    {
        User::where('id', $userId)->update([
            'referred_by_user_id' => $referrerId
        ]);
    }
    public function getUserPoints(int $userId)
    {
        $userPoint = UserPoint::where('user_id', $userId)->first();
        if (!$userPoint) return 0;
        return $userPoint->points;
    }
    public function getMilestonesByPoints(int $points)
    {
        return ReferralMilestone::where('required_points', '<=', $points)->get();
    }

    public function userHasMilestoneReward(int $userId, int $milestoneId): bool
    {
        return UserReward::where('user_id', $userId)
            ->where('referral_milestone_id', $milestoneId)
            ->exists();
    }
    public function createUserReward(int $userId, int $milestoneId, bool $redeemed = false)
    {
        return UserReward::create([
            'user_id' => $userId,
            'referral_milestone_id' => $milestoneId,
            'redeemed' => $redeemed,
        ]);
    }
    public function decrementUserPoints(int $userId, int $points)
    {
        $userPoint = UserPoint::where('user_id', $userId)->first();

        $userPoint->points = max(0, ($userPoint->points ?? 0) - $points);
        $userPoint->save();
    }
    public function markAsRedeemed(int $rewardId)
    {
        $reward = UserReward::find($rewardId);
        $reward->update(['redeemed' => true]);
    }
  

}
