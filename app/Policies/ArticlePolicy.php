<?php

namespace App\Policies;

use App\Models\Articles;
use App\Models\Users;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    public function update(Users $user, Articles $article): bool
    {
        return $user->id === $article->created_by;
    }

    public function delete(Users $user, Articles $article): bool
    {
        return $user->id === $article->created_by;
    }
}
