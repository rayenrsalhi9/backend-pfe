<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentAuditTrailRepositoryInterface extends BaseRepositoryInterface
{
    public function getDocumentAuditTrailsCount($attributes);
    public function getDocumentAuditTrails($attributes);
    public function saveDocumentAuditTrail($attributes);
}
