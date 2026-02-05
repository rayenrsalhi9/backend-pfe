<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentCommentRepositoryInterface extends BaseRepositoryInterface
{
     public function getDocumentComment($id);
     public function deleteDocumentComments($id);

}
