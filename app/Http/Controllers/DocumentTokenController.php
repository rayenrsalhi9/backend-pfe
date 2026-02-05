<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DocumentTokenRepositoryInterface;

class DocumentTokenController extends Controller
{
    private $documentTokenRepository;

    public function __construct(DocumentTokenRepositoryInterface $documentTokenRepository)
    {
        $this->documentTokenRepository = $documentTokenRepository;
    }

    public function getDocumentToken($id)
    {
        $token = $this->documentTokenRepository->getDocumentToken($id);
        return ["result" => $token];
    }

    public function deleteDocumentToken($token)
    {
        return response($this->documentTokenRepository->deleteDocumentToken($token), 204);
    }
}
