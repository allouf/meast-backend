<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\General\ReferralRuleRequest;
use App\Http\Requests\Admin\General\ReferralMilestoneRequest;
use App\Http\Resources\Admin\General\ReferralRuleResource;
use App\Http\Resources\Admin\General\ReferralMilestoneResource;
use App\Http\Resources\Admin\User\UserResource;
use App\Services\ReferralService;
use App\Models\ReferralLog;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\PaginateTrait;
use Exception;

class ReferralController extends Controller
{
    use PaginateTrait;
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    // -------------------- RULES --------------------
    public function getRules()
    {
        try {
            $rules = $this->referralService->listRules();
            return $this->successResponse(__('fetch_success'), ReferralRuleResource::collection($rules));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function createRule(ReferralRuleRequest $request)
    {
        try {
            $rule = $this->referralService->createRule($request->validated());
            return $this->successResponse(__('create_success'), new ReferralRuleResource($rule));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateRule(ReferralRuleRequest $request, $id)
    {
        try {
            $rule = $this->referralService->updateRule($id, $request->validated());
            return $this->successResponse(__('update_success'), new ReferralRuleResource($rule));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteRule($id)
    {
        try {
            $this->referralService->deleteRule($id);
            return $this->successResponse(__('delete_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    // -------------------- MILESTONES --------------------
    public function getMilestones()
    {
        try {
            $milestones = $this->referralService->listMilestones();
            return $this->successResponse(__('fetch_success'), ReferralMilestoneResource::collection($milestones));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function createMilestone(ReferralMilestoneRequest $request)
    {
        try {
            $milestone = $this->referralService->createMilestone($request->validated());
            return $this->successResponse(__('create_success'), new ReferralMilestoneResource($milestone));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateMilestone(ReferralMilestoneRequest $request, $id)
    {
        try {
            $milestone = $this->referralService->updateMilestone($id, $request->validated());
            return $this->successResponse(__('update_success'), new ReferralMilestoneResource($milestone));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteMilestone($id)
    {
        try {
            $this->referralService->deleteMilestone($id);
            return $this->successResponse(__('delete_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

   // -------------------- Statistics --------------------

   public function referralLogs()
{
    try {
        $referralQuery = ReferralLog::with(['referrer', 'referredUser'])->latest();

        $paginatedReferrals = $this->paginate($referralQuery);

        return $this->successResponse('Referral logs retrieved successfully', [
            'logs' => collect($paginatedReferrals['data'])->map(function ($log) {
                return [
                    'id'         => $log->id,
                    'status'     => $log->status,
                    'action'     => $log->action,
                    'points'     => $log->points_earned,
                    'created_at' => $log->created_at,
                    'referrer' => $log->referrer ? [
                        'id'    => $log->referrer->id,
                        'name'  => $log->referrer->name,
                        'email' => $log->referrer->email,
                    ] : null,
                    'referred_user' => $log->referredUser ? [
                        'id'    => $log->referredUser->id,
                        'name'  => $log->referredUser->name,
                        'email' => $log->referredUser->email,
                    ] : null,
                ];
            }),
            'meta' => $paginatedReferrals['meta'],
        ]);
    } catch (Exception $e) {
        return $this->exceptionResponse($e);
    }
 }
   public function referralStatistics()
   {
       try {
           $totalReferrals = ReferralLog::count();
           $successfulReferrals = ReferralLog::where('status', 'approved')->count();
           $totalPoints = DB::table('user_points')->sum('points');

           $topReferrers = ReferralLog::select('referrer_id', DB::raw('COUNT(*) as total'))
               ->groupBy('referrer_id')
               ->orderByDesc('total')
               ->take(5)
               ->with('referrer:id,name,email') // assuming 'name' exists
               ->get();

           return $this->successResponse('Referral statistics', [
               'total_referrals' => $totalReferrals,
               'successful_referrals' => $successfulReferrals,
               'total_points_distributed' => $totalPoints,
               'top_referrers' => $topReferrers,
           ]);
       } catch (Exception $e) {
           return $this->exceptionResponse($e);
       }
   }

   // تفاصيل مستخدم معين في نظام الإحالة
   public function userReferralInfo($userId)
   {
       try {
           $user = User::findOrFail($userId);
   
           $referralQuery = ReferralLog::with('referredUser')
               ->where('referrer_id', $userId)
               ->latest();
   
           $paginatedReferrals = $this->paginate($referralQuery);
           $points = UserPoint::where('user_id', $userId)->value('points');
           $rewards = UserReward::where('user_id', $userId)->with('milestone')->get();
   
           return $this->successResponse('User referral data', [
               'user' => new UserResource($user),
               'referral_count' => $referralQuery->count(),
               'points' => (int) $points,
               'rewards' => $rewards,
               'logs' => [
                   'data' => collect($paginatedReferrals['data'])->map(function ($log) {
                       return [
                           'id'         => $log->id,
                           'status'     => $log->status,
                           'action'     => $log->action,
                           'points'     => $log->points_earned,
                           'created_at' => $log->created_at,
                           'referred_user' => $log->referredUser
                               ? [
                                   'id' => $log->referredUser->id,
                                   'name' => $log->referredUser->name,
                                   'email' => $log->referredUser->email,
                               ]
                               : null,
                       ];
                   }),
                   'meta' => $paginatedReferrals['meta'],
               ],
           ]);
       } catch (Exception $e) {
           return $this->exceptionResponse($e);
       }
   }
   
}
