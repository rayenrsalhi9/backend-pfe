<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentRepositoryInterface extends BaseRepositoryInterface
{
    public function getDocuments($attributes);
    public function saveDocument($request, $path);
    public function getDocumentsCount($attributes);
    public function updateDocument($request, $id);
    public function assignedDocuments($attributes);
    public function assignedDocumentsCount($attributes);
    public function getDocumentByCategory();
    public function getDocumentbyId($id);
}
