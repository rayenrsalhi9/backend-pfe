<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface RoleUsersRepositoryInterface extends BaseRepositoryInterface
{
    public function getRoleUsers($id);
    public function updateRoleUsers($roleId, $request);
}
