<?php

namespace App\Policies;

use App\Models\Blogs;
use App\Models\Users;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the blog.
     */
    public function update(Users $user, Blogs $blog): bool
    {
        return $user->id === $blog->created_by;
    }

    /**
     * Determine whether the user can delete the blog.
     */
    public function delete(Users $user, Blogs $blog): bool
    {
        return $user->id === $blog->created_by;
    }
}
