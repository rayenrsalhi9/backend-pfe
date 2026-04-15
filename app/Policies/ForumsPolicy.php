<?php

namespace App\Policies;

use App\Models\Forums;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ForumsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the forum.
     * Uses permission claims from JWT token for authorization.
     */
    public function delete($user, Forums $forum): bool
    {
        try {
            $claims = Auth::parseToken()->getPayload()->get('claims');
            return is_array($claims) && in_array('FORUM_DELETE_TOPIC', $claims);
        } catch (\Exception $e) {
            return false;
        }
    }
}