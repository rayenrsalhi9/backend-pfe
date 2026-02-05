<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface CompanyProfileRepositoryInterface extends BaseRepositoryInterface
{
    public function getCompanyProfile();
    public function updateCompanyProfile($attribute);
}
