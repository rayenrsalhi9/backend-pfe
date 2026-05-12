<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentPermissionRepositoryInterface extends BaseRepositoryInterface
{
     public function getDocumentPermissionList($id);
     public function addDocumentUserPermission($request);
     public function deleteDocumentUserPermission($id);
     public function getIsDownloadFlag($id, $isPermission);
}
