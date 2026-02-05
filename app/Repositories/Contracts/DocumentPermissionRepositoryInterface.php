<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentPermissionRepositoryInterface extends BaseRepositoryInterface
{
     public function getDocumentPermissionList($id);
     public function addDocumentRolePermission($request);
     public function addDocumentUserPermission($request);
     public function multipleDocumentsToUsersAndRoles($request);
     public function deleteDocumentUserPermission($id);
     public function deleteDocumentRolePermission($id);
     public function getIsDownloadFlag($id, $isPermission);
}
