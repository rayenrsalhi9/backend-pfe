<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\CompanyProfileRepositoryInterface;

class CompanyProfileController extends Controller
{
    private $companyProfileRepository;

    public function __construct(CompanyProfileRepositoryInterface $companyProfileRepository)
    {
        $this->companyProfileRepository = $companyProfileRepository;
    }

    public function getCompanyProfile()
    {
        return response()->json($this->companyProfileRepository->getCompanyProfile());
    }

    public function updateCompanyProfile(Request $request)
    {
        return response()->json($this->companyProfileRepository->updateCompanyProfile($request->all()));
    }
}
