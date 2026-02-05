<?php

namespace App\Repositories\Implementation;

use App\Models\RoleClaims;
use App\Models\Roles;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;

//use Your Model

/**
 * Class RoleRepository.
 */
class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return Roles::class;
    }


    public function getRolesForDropdown()
    {
        $roles = $this->model->get();
        return $roles;
    }

    public function findRole($id)
    {
        $model = $this->model->with('roleClaims')->findOrFail($id);
        $this->resetModel();
        return $this->parseResult($model);
    }

    public function createRole(array $attributes)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->newInstance($attributes);
            $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);
            foreach ($attributes['roleClaims'] as $roleId) {
                $model = RoleClaims::create(array(
                    'actionId' =>  $roleId['actionId'],
                    'claimType' => $roleId['claimType'],
                    'claimValue' => $roleId['claimValue'],
                    'roleId' =>  $result->id,
                ));
                $model->save();
            }

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }


    public function updateRoleClaim($model, $id, $userRoles)
    {
        try {
            DB::beginTransaction();
            $roleclaim = RoleClaims::where('roleId', '=', $id)->get('id');
            RoleClaims::destroy($roleclaim);
            $result = $this->parseResult($model);

            foreach ($userRoles as $action) {
                RoleClaims::create(array(
                    'roleId' =>    $action['roleId'],
                    'actionId' =>  $action['actionId'],
                    'claimType' => $action['claimType'],
                    'claimValue' => $action['claimValue'],
                ));
            }

            $model->save();

            $this->resetModel();

            $result = $this->parseResult($model);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }
}
