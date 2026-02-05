<?php

namespace App\Repositories\Implementation;

use App\Models\UserClaims;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\UserClaimRepositoryInterface;
use Illuminate\Support\Facades\DB;
//use Your Model

/**
 * Class UserRepository.
 */
class UserClaimRepository extends BaseRepository implements UserClaimRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return UserClaims::class;
    }

    public function updateUserClaim($id, $userclaims)
    {
        try {
            DB::beginTransaction();
            $userclaim = UserClaims::where('userId', $id)->get();
            UserClaims::destroy($userclaim);

            foreach ($userclaims as $action) {

                UserClaims::create(array(
                    'userId' =>    $action['userId'],
                    'actionId' =>  $action['actionId'],
                    'claimType' => $action['claimType'],
                    'claimValue' => $action['claimValue'],
                ));
            }
            $this->resetModel();
            DB::commit();
            return [];
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }
}
