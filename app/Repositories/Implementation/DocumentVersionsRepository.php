<?php

namespace App\Repositories\Implementation;

use App\Models\Documents;
use App\Models\DocumentVersions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentVersionsRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use Illuminate\Support\Facades\DB;
use  App\Repositories\Contracts\DocumentRepositoryInterface as DocumentRepository;

//use Your Model

/**
 * Class DocumentVersionsRepository.
 */
class DocumentVersionsRepository extends BaseRepository implements DocumentVersionsRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    private $documentRepo;

    /**
     * Class constructor
     *
     * @param DocumentRepository $roleRepo
     */
    public function __construct()
    {
        $this->documentRepo = app('App\Repositories\Contracts\DocumentRepositoryInterface');
    }

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return DocumentVersions::class;
    }

    public function getDocumentversion($id)
    {
        $model = Documents::with('users')->findOrFail($id);
        if ($model == null) {
            return $this->model();
        }

        $documentsVersions = DocumentVersions::select(['documentVersions.id', 'documentVersions.url', 'documentVersions.modifiedDate',  DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByUser")])
            ->leftJoin('users', 'documentVersions.createdBy', '=', 'users.id')
            ->where('documentVersions.documentId', $id)
            ->orderBy('documentVersions.modifiedDate', 'desc')
            ->get();


        $data = $model->modifiedDate->format("Y-m-d H:i:s");

        $results = $documentsVersions->push([
            'isCurrentVersion' => true,
            'id' => $model->id,
            'url' => $model->url,
            'modifiedDate' => $data,
            'createdByUser' =>  $model->users->firstName  . ' ' . $model->users->lastName
        ]);

        $array = collect($results)->sortByDesc('modifiedDate')->values();

        return  $array->toArray();
    }

    public function saveNewDocumentVersion($request, $path)
    {
        try {
            DB::beginTransaction();
            $documentModel = Documents::findOrFail($request->documentId);
            if ($documentModel == null) {
                throw new RepositoryException('Document Not Found.');
            }

            $model = DocumentVersions::create([
                'url' => $documentModel->url,
                'documentId' => $documentModel->id,
                'createdBy' => $documentModel->createdBy,
                'modifiedBy' => $documentModel->modifiedBy,
            ]);

            $model->createdDate = $documentModel->createdDate;
            $model->modifiedDate = $documentModel->modifiedDate;

            $model->save();

            $userId = Auth::parseToken()->getPayload()->get('userId');
            $documentModel->url = $path;
            $documentModel->createdDate = Carbon::now()->addSeconds(2);
            $documentModel->modifiedDate = Carbon::now()->addSeconds(2);
            $documentModel->createdBy = $userId;

            $documentModel->save();

            $result = $this->parseResult($model);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function restoreDocumentVersion($id, $versionId)
    {
        try {
            DB::beginTransaction();
            $documentModel = Documents::findOrFail($id);
            if ($documentModel == null) {
                throw new RepositoryException('Document Not Found.');
            }

            $version = DocumentVersions::findOrFail($versionId);

            if ($version  == null) {
                throw new RepositoryException('Document version Not Found.');
            }

            $newVersion = DocumentVersions::create([
                'url' => $documentModel->url,
                'documentId' => $documentModel->id,
                'createdBy' => $documentModel->createdBy,
                'modifiedBy' => $documentModel->modifiedBy,
            ]);

            $newVersion->createdDate = $documentModel->createdDate;
            $newVersion->modifiedDate = $documentModel->modifiedDate;

            $newVersion->save();

            $userId = Auth::parseToken()->getPayload()->get('userId');
            $documentModel->url = $version->url;
            $documentModel->createdBy = $userId;
            $documentModel->modifiedDate = Carbon::now()->addSeconds(2);
            $documentModel->createdDate = Carbon::now()->addSeconds(2);

            $documentModel->save();

            $result = $this->parseResult($newVersion);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }
}
