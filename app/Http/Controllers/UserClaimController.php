<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\UserClaimRepositoryInterface;

class UserClaimController extends Controller
{
    private $userclaimRepository;

    public function __construct(UserClaimRepositoryInterface $userclaimRepository)
    {
        $this->userclaimRepository = $userclaimRepository;
    }

    public function index()
    {
        return response()->json($this->userclaimRepository->all());
    }

    public function create(Request $request)
    {
        return  response()->json($this->userclaimRepository->create($request->all()), 201);
    }

    public function edit($id)
    {
        return response()->json($this->userclaimRepository->find($id));
    }

    public function update(Request $request, $id)
    {
        if (count($request['userClaims']) == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please Select at least one Permission',
            ], 500);
        }
        return  response()->json($this->userclaimRepository->updateUserClaim($id, $request['userClaims']), 200);
    }

    public function destroy($id)
    {
        return response($this->userclaimRepository->delete($id), 204);
    }
}
