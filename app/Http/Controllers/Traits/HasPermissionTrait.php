<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Auth;

trait HasPermissionTrait
{
    /**
     * Check if authenticated user has a specific permission claim
     * Uses claims from JWT token for authorization
     */
    private function hasPermission(string $claim): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        try {
            $claims = Auth::parseToken()->getPayload()->get('claims');
            return is_array($claims) && in_array($claim, $claims);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user has ANY of the provided claims (OR logic)
     */
    private function hasAnyPermission(array $claims): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        try {
            $userClaims = Auth::parseToken()->getPayload()->get('claims');
            if (!is_array($userClaims))
                return false;
            
            foreach ($claims as $claim) {
                if (in_array($claim, $userClaims))
                    return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user has ALL of the provided claims (AND logic)
     */
    private function hasAllPermissions(array $claims): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        try {
            $userClaims = Auth::parseToken()->getPayload()->get('claims');
            if (!is_array($userClaims))
                return false;
            
            foreach ($claims as $claim) {
                if (!in_array($claim, $userClaims))
                    return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}