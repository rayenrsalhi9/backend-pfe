<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\LoginAuditRepositoryInterface;

class LoginAuditController extends Controller
{
    private $loginAuditRepository;
    protected $queryString;

    public function __construct(LoginAuditRepositoryInterface $loginAuditRepository)
    {
        $this->loginAuditRepository = $loginAuditRepository;
    }

    public function getLoginAudit(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->loginAuditRepository->getLoginAuditsCount($queryString);
        return response()->json($this->loginAuditRepository->getLoginAudits($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }
}
