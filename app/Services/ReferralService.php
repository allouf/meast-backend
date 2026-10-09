<?php

namespace App\Services;

use App\Models\ReferralMilestone;
use App\Models\ReferralRule;
use App\Models\User;
use App\Models\UserReward;
use App\Repositories\Interfaces\ReferralRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    protected $referralRepository;

    public function __construct(ReferralRepositoryInterface $referralRepository)
    {
        $this->referralRepository = $referralRepository;
    }

    public function listRules()
    {
        return $this->referralRepository->getAllRules();
    }

    public function getRule($id)
    {
        return $this->referralRepository->findRuleById($id);
    }

    public function createRule(array $data)
    {
        return $this->referralRepository->createRule($data);
    }

    public function updateRule($id, array $data)
    {
        return $this->referralRepository->updateRule($id, $data);
    }

    public function deleteRule($id)
    {
        return $this->referralRepository->deleteRule($id);
    }

    public function listMilestones()
    {
        return $this->referralRepository->getAllMilestones();
    }

    public function getMilestone($id)
    {
        return $this->referralRepository->findMilestoneById($id);
    }

    public function createMilestone(array $data)
    {
        return $this->referralRepository->createMilestone($data);
    }

    public function updateMilestone($id, array $data)
    {
        return $this->referralRepository->updateMilestone($id, $data);
    }

    public function deleteMilestone($id)
    {
        return $this->referralRepository->deleteMilestone($id);
    }
    public function handleReferral(?string $referralCode, int $newUserId, string $action)
    {
        if (!$referralCode) return;

        DB::beginTransaction();

        try {
            $referrer = User::where('ref_code', $referralCode)->first();
            if (!$referrer) {
                DB::rollBack();
                throw new HttpException(422, __('invalid_referral_code'));
            }

            $this->referralRepository->updateReferredBy($newUserId, $referrer->id);

            $rule = ReferralRule::where('action_type', $action)->first();
            $points = $rule?->points ?? 0;

            $this->referralRepository->createReferralLog([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $newUserId,
                'action' => $action,
                'points_earned' => $points,
                'status' => 'approved',
            ]);

            $this->referralRepository->incrementUserPoints($referrer->id, $points);
            $this->referralRepository->createPointTransaction($referrer->id, $points, $action);

            $referralPoints = $this->referralRepository->getUserPoints($referrer->id);
            $milestones = $this->referralRepository->getMilestonesByPoints($referralPoints);

            foreach ($milestones as $milestone) {
                $alreadyHasReward = $this->referralRepository->userHasMilestoneReward($referrer->id, $milestone->id);
                $cost = (int) $milestone->required_points;

                if (!$alreadyHasReward && $referralPoints >= $cost) {
                    $this->referralRepository->createPointTransaction(
                        $referrer->id,
                        -$cost,
                        'Milestone auto reward: ' . $milestone->reward_type
                    );

                    $this->referralRepository->createUserReward($referrer->id, $milestone->id, false);

                    $referralPoints -= $cost;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function claimReward(int $userId, int $rewardId): bool
    {
        $reward = UserReward::findOrFail($rewardId);
        $userPoints = $this->referralRepository->getUserPoints($userId);
        $milestone = $reward->milestone;
        $requiredPoints = (int) $milestone->required_points;

        $this->referralRepository->decrementUserPoints($userId, $requiredPoints);

        $this->referralRepository->createPointTransaction(
            $userId,
            -$requiredPoints,
            'Claimed reward: ' . $milestone->reward_type
        );

        $this->referralRepository->markAsRedeemed($rewardId, true);

        return true;
    }
}
