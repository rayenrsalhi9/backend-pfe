<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{
     public function findRole($id);
     public function createRole(array $attributes);
     public function updateRoleClaim($model, $id, $userRoles);
     public function getRolesForDropdown();
}
