<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentMetaDataRepositoryInterface extends BaseRepositoryInterface
{
    public function addMetadata($metadata);
    public function getDocumentMetadatas($id);
}
