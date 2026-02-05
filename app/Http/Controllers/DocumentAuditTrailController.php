<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentAuditTrails;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Contracts\DocumentAuditTrailRepositoryInterface;

class DocumentAuditTrailController extends Controller
{
    private $documentAuditTrailRepository;
    protected $queryString;

    public function __construct(DocumentAuditTrailRepositoryInterface $documentAuditTrailRepository)
    {
        $this->documentAuditTrailRepository = $documentAuditTrailRepository;
    }

    public function getDocumentAuditTrails(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->documentAuditTrailRepository->getDocumentAuditTrailsCount($queryString);
        return response()->json($this->documentAuditTrailRepository->getDocumentAuditTrails($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    function documentsTransactions()
    {
        $currentYear = now()->year;

        $monthlyOperations = DocumentAuditTrails::select(
            DB::raw('MONTH(createdDate) as month'),
            DB::raw('SUM(CASE WHEN operationName = "Download" THEN 1 ELSE 0 END) as download_count'),
            DB::raw('SUM(CASE WHEN operationName = "Created" THEN 1 ELSE 0 END) as upload_count')
        )
            ->whereYear('createdDate', $currentYear)
            ->whereIn('operationName', ['Download', 'Created'])
            ->groupBy(DB::raw('MONTH(createdDate)'))
            ->get();

        return response()->json($monthlyOperations);
    }

    public function downloadDocument($id)
    {
        $file = Documents::findOrFail($id);
        $fileupload = $file->url;

        if (Storage::disk('local')->exists($fileupload)) {
            $file_contents = Storage::disk('local')->get($fileupload);
            $fileType = Storage::mimeType($fileupload);

            $fileExtension = explode('.', $file->url);
            return response($file_contents)
                ->header('Cache-Control', 'no-cache private')
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', $fileType)
                ->header('Content-length', strlen($file_contents))
                ->header('Content-Disposition', 'attachment; filename=' . $file->name . '.' . $fileExtension[1])
                ->header('Content-Transfer-Encoding', 'binary');
        }
    }

    public function fileUpload()
    {
        return view('fileUpload');
    }

    public function saveDocumentAuditTrail(Request $request)
    {
        return response()->json($this->documentAuditTrailRepository->saveDocumentAuditTrail($request));
    }

    public function deleteDocument($id)
    {
        return response($this->documentAuditTrailRepository->delete($id), 204);
    }
}
