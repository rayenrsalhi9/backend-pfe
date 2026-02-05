<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface LoginAuditRepositoryInterface extends BaseRepositoryInterface
{
    public function getLoginAudits($attributes);
    public function getLoginAuditsCount($attributes);
}
