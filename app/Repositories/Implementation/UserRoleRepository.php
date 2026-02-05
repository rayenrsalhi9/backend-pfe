<?php

namespace App\Repositories\Implementation;

use App\Models\UserRoles;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\UserRoleRepositoryInterface;


//use Your Model

/**
 * Class UserRoleRepository.
 */
class UserRoleRepository extends BaseRepository implements UserRoleRepositoryInterface
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
        return UserRoles::class;
    }

}
