<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function createUser(array $attributes);
    public function findUser($id);
    public function updateUser($model, $id, $userRoles);
    public function updateUserProfile($request);
    public function getUsersForDropdown();
}
