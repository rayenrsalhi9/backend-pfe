<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\DocumentVersionsRepositoryInterface;
use Ramsey\Uuid\Uuid;

class DocumentVersionController extends Controller
{
    private $documentVersionRepository;

    public function __construct(DocumentVersionsRepositoryInterface $documentVersionRepository)
    {
        $this->documentVersionRepository = $documentVersionRepository;
    }

    public function index($id)
    {
        return response()->json($this->documentVersionRepository->getDocumentversion($id));
    }

    public function saveNewVersionDocument(Request $request)
    {
        $path = $request->file('uploadFile')->storeAs(
            'documents',
            Uuid::uuid4() . '.' . $request->file('uploadFile')->getClientOriginalExtension()
        );
        return response()->json($this->documentVersionRepository->saveNewDocumentVersion($request, $path), 201);
    }

    public function restoreDocumentVersion($id, $versionId)
    {
        return response()->json($this->documentVersionRepository->restoreDocumentVersion($id, $versionId), 201);
    }
}
