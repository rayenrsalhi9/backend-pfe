<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface UserClaimRepositoryInterface extends BaseRepositoryInterface
{
     public function updateUserClaim($id, $userRoles);
}
