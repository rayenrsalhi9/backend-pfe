<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentVersionsRepositoryInterface extends BaseRepositoryInterface
{
    public function getDocumentversion($id);
    public function saveNewDocumentVersion($request, $path);
    public function restoreDocumentVersion($id, $versionId);
}
