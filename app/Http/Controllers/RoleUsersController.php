<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\RoleUsersRepositoryInterface;

class RoleUsersController extends Controller
{
    private $roleUsersRepository;

    public function __construct(RoleUsersRepositoryInterface $roleUsersRepository)
    {
        $this->roleUsersRepository = $roleUsersRepository;
    }

    public function getRoleUsers($roleId)
    {
        return response()->json($this->roleUsersRepository->getRoleUsers($roleId));
    }

    public function updateRoleUsers(Request $request, $roleId)
    {
        return response()->json($this->roleUsersRepository->updateRoleUsers($roleId,$request));
    }
}
