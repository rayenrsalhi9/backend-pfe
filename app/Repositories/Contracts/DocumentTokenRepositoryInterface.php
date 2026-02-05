<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentTokenRepositoryInterface extends BaseRepositoryInterface
{
    public function getDocumentToken($id);
    public function deleteDocumentToken($token);
    public function getDocumentPathByToken($id, $request);
}
