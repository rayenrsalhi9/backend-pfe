<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\RoleRepositoryInterface;


class RoleController extends Controller
{
    private $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index()
    {
        return response()->json($this->roleRepository->all());
    }

    public function dropdown()
    {
        return response()->json($this->roleRepository->getRolesForDropdown());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => "required|unique:roles,name,NULL,id,deleted_at,NULL",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 409);
        }

        return  response($this->roleRepository->createRole($request->all()), 201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->roleRepository->findRole($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $model = Roles::where([['name', '=', $request->name], ['id', '<>', $id]])->first();

        if (!is_null($model)) {
            return response()->json([
                'message' => 'Role Name Already Exist.',
            ], 409);
        }
        $model = $this->roleRepository->find($id);
        $model->name = $request->name;
        if (count($request['roleClaims']) == 0) {
            return response()->json([
                'message' => 'Please Select at least one Permission',
            ], 500);
        }
        return  response()->json($this->roleRepository->updateRoleClaim($model, $id, $request['roleClaims']), 200);
    }

    public function destroy($id)
    {
        return response($this->roleRepository->delete($id), 204);
    }
}
