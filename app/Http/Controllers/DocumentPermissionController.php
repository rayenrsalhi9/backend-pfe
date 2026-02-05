<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\DocumentPermissionRepositoryInterface;

class DocumentPermissionController extends Controller
{
    private $documentPermissionRepository;

    public function __construct(DocumentPermissionRepositoryInterface $documentPermissionRepository)
    {
        $this->documentPermissionRepository = $documentPermissionRepository;
    }

    public function index()
    {
        return response()->json($this->documentPermissionRepository->all());
    }

    public function addDocumentRolePermission(Request $request)
    {
        return  response()->json($this->documentPermissionRepository->addDocumentRolePermission($request->all()));
    }

    public function addDocumentUserPermission(Request $request)
    {
        return  response()->json($this->documentPermissionRepository->addDocumentUserPermission($request->all()));
    }

    public function multipleDocumentsToUsersAndRoles(Request $request)
    {
        return  response()->json($this->documentPermissionRepository->multipleDocumentsToUsersAndRoles($request->all()));
    }

    public function edit($id)
    {
        return response()->json($this->documentPermissionRepository->getDocumentPermissionList($id));
    }

    public function deleteDocumentUserPermission($id)
    {
        return response()->json($this->documentPermissionRepository->deleteDocumentUserPermission($id));
    }

    public function deleteDocumentRolePermission($id)
    {
        return response()->json($this->documentPermissionRepository->deleteDocumentRolePermission($id));
    }

    public function getIsDownloadFlag($id, $isPermission)
    {
        return response()->json($this->documentPermissionRepository->getIsDownloadFlag($id, $isPermission));
    }
}
