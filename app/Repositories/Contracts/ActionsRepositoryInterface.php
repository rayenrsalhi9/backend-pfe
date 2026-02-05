<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface ActionsRepositoryInterface extends BaseRepositoryInterface
{
    public function createAction(array $attribute);
}
