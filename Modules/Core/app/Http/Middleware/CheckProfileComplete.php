<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user && !$user->is_profile_complete) {
            return response()->json([
                'status' => false,
                'message' => 'Your profile is not complete. Please complete your profile to continue.',
            ], 475);
        }
        return $next($request);
    }
}
