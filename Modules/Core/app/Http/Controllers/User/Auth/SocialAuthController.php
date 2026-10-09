<?php

namespace Modules\Core\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use Modules\Core\Models\User;
use Modules\Core\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(protected TokenService $tokenService) {}

    public function redirectToProvider(string $provider)
    {
        $this->validateProvider($provider);
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(Request $request, string $provider)
    {
        $this->validateProvider($provider);
        try {
            if ($request->has('id_token')) {
                $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->id_token);
            } else {
                $socialUser = Socialite::driver($provider)->stateless()->user();
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $this->resolveName($socialUser, $provider),
                'provider_id' => $socialUser->getId(),
                'provider' => $provider,
                'password' => bcrypt(Str::random(24)),
                'is_profile_complete' => false,
                'verified_at' => now(),
            ]
        );
        if (!$user->hasRole('consumer')) {
            $user->assignRole('consumer');
        }
        $tokens = $this->tokenService->generateTokens($user, request()->header('User-Agent'), request()->ip());
        if (!$request->has('id_token')) {
            $redirectUrl = config('app.frontend_url') . '/login-success?refresh_token=' . $tokens['refresh_token'];
            return redirect($redirectUrl);
        }
        return $this->successResponse('success', ['user' => $user, 'tokens' => $tokens]);
    }


    protected function validateProvider(string $provider)
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return $this->errorResponse("Provider not supported", 400);
        }
    }

    protected function resolveName($socialUser, string $provider): string
    {
        if ($provider === 'apple' && empty($socialUser->getName())) {
            return 'Apple User';
        }
        return $socialUser->getName() ?? $socialUser->getNickname();
    }
}
