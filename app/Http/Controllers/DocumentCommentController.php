<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\DocumentCommentRepositoryInterface;

class DocumentCommentController extends Controller
{
    private $documentCommentRepository;

    public function __construct(DocumentCommentRepositoryInterface $documentCommentRepository)
    {
        $this->documentCommentRepository = $documentCommentRepository;
    }

    public function index($id)
    {
        return response()->json($this->documentCommentRepository->getDocumentComment($id));
    }

    public function destroy($id)
    {
        return response($this->documentCommentRepository->deleteDocumentComments($id), 204);
    }

    public function saveDocumentComment(Request $request)
    {
        return  response($this->documentCommentRepository->create($request->all()), 201);
    }
}
