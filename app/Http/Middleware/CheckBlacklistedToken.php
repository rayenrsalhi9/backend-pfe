<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class CheckBlacklistedToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = Auth::getToken();
            if ($token) {
                $tokenHash = hash('sha256', $token->get());
                
                // Check if token is blacklisted
                $blacklisted = DB::table('jwt_blacklist')
                    ->where('token_hash', $tokenHash)
                    ->where('expires_at', '>', now())
                    ->exists();
                
                if ($blacklisted) {
                    return response()->json(['error' => 'Token has been revoked'], 401);
                }
            }
        } catch (\Exception $e) {
            // Token might not be present or valid, let other middleware handle it
            // Database or system error - fail closed for security
            Log::error('Blacklist check failed: ' . $e->getMessage());
            return response()->json(['error' => 'Authentication service unavailable'], 503);
        }

        return $next($request);
    }
}