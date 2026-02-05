<?php

namespace App\Http\Controllers;

use Ramsey\Uuid\Uuid;
use App\Models\Documents;
use Illuminate\Http\Request;
use App\Models\DocumentVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\CommonMark\Node\Block\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\DocumentTokenRepositoryInterface;
use App\Repositories\Contracts\DocumentMetaDataRepositoryInterface;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;

class DocumentController extends Controller
{
    private $documentRepository;
    private  $documentMetaDataRepository;
    private $documenTokenRepository;
    private $userNotificationRepository;
    protected $queryString;

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        DocumentMetaDataRepositoryInterface $documentMetaDataRepository,
        UserNotificationRepositoryInterface $userNotificationRepository,
        DocumentTokenRepositoryInterface $documenTokenRepository
    ) {
        $this->documentRepository = $documentRepository;
        $this->documentMetaDataRepository = $documentMetaDataRepository;
        $this->userNotificationRepository = $userNotificationRepository;
        $this->documenTokenRepository = $documenTokenRepository;
    }

    public function getDocuments(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->documentRepository->getDocumentsCount($queryString);
        return response()->json($this->documentRepository->getDocuments($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function officeviewer(Request $request, $id)
    {
        $isTokenAvailable = $this->documenTokenRepository->getDocumentPathByToken($id, $request);

        if ($isTokenAvailable == false) {
            return response()->json([
                'message' => 'Document Not Found.',
            ], 404);
        }
        return $this->downloadDocument($id, $request->input('isVersion'));
    }


    public function downloadDocument($id, $isVersion)
    {
        $bool = filter_var($isVersion, FILTER_VALIDATE_BOOLEAN);
        if ($bool == true) {
            $file = DocumentVersions::findOrFail($id);
        } else {
            $file = Documents::findOrFail($id);
        }

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

    public function readTextDocument($id, $isVersion)
    {
        $bool = filter_var($isVersion, FILTER_VALIDATE_BOOLEAN);
        if ($bool == true) {
            $file = DocumentVersions::findOrFail($id);
        } else {
            $file = Documents::findOrFail($id);
        }

        $fileupload = $file->url;

        if (Storage::disk('local')->exists($fileupload)) {
            $file_contents = Storage::disk('local')->get($fileupload);
            $response = ["result" => [$file_contents]];
            return response($response);
        }
    }

    public function fileUpload()
    {
        return view('fileUpload');
    }

    public function countByExtension()
    {
        $documents = Documents::whereNotNull('extension')->groupBy('extension')->select('extension', DB::raw('count(*) as total'))->get();
        $totalDocuments = Documents::whereNotNull('extension')->count();
        $response = [
            'documents' => $documents,
            'total_documents' => $totalDocuments
        ];
        return response()->json($response, 200);
    }

    public function saveDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 409);
        }

        $path = $request->file('uploadFile')->storeAs(
            'documents',
            Uuid::uuid4() . '.' . $request->file('uploadFile')->getClientOriginalExtension()
        );

        return $this->documentRepository->saveDocument($request, $path);
    }

    public function updateDocument(Request $request, $id)
    {
        $model = Documents::where([['name', '=', $request->name], ['id', '<>', $id]])->first();

        if (!is_null($model)) {
            return response()->json([
                'message' => 'Document already exist.',
            ], 409);
        }
        return  response()->json($this->documentRepository->updateDocument($request, $id), 200);
    }

    public function deleteDocument($id)
    {
        return response($this->documentRepository->delete($id), 204);
    }

    public function getDocumentMetatags($id)
    {
        return  response($this->documentMetaDataRepository->getDocumentMetadatas($id), 200);
    }

    public function assignedDocuments(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->documentRepository->assignedDocumentsCount($queryString);
        return response()->json($this->documentRepository->assignedDocuments($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function getDocumentsByCategoryQuery()
    {
        return response()->json($this->documentRepository->getDocumentByCategory());
    }

    public function getDocumentbyId($id)
    {
        $this->userNotificationRepository->markAsReadByDocumentId($id);
        return response()->json($this->documentRepository->getDocumentbyId($id));
    }
}
