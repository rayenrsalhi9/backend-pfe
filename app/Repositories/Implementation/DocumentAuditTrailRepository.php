<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentOperationEnum;
use App\Models\DocumentAuditTrails;
use App\Models\DocumentMetaDatas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentAuditTrailRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;


/**
 * Class DocumentAuditTrails.
 */
class DocumentAuditTrailRepository extends BaseRepository implements DocumentAuditTrailRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;
    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return DocumentAuditTrails::class;
    }

    public function getDocumentAuditTrails($attributes)
    {

        $query = DocumentAuditTrails::select(['documentAuditTrails.*', 'documents.name as documentName', 'categories.name as categoryName', 'categories.name as categoryName', 'roles.name as permissionRole', DB::raw("CONCAT(userRole.firstName,' ', userRole.lastName) as permissionUser"), DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdBy")])
            ->join('documents', 'documentAuditTrails.documentId', '=', 'documents.id')
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documentAuditTrails.createdBy', '=', 'users.id')
            ->leftJoin('roles', 'documentAuditTrails.assignToRoleId', '=', 'roles.id')
            ->leftJoin('users as userRole', 'documentAuditTrails.assignToUserId', '=', 'userRole.id');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'documentName') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'createdDate') {
            $query = $query->orderBy('documentAuditTrails.createdDate', $direction);
        } else if ($orderBy == 'createdBy') {
            $query = $query->orderBy('users.firstName', $direction);
        } else if ($orderBy == 'operationName') {
            $query = $query->orderBy('documentAuditTrails.operationName', $direction);
        } else if ($orderBy == 'permissionRole') {
            $query = $query->orderBy('roles.name', $direction);
        } else if ($orderBy == 'permissionUser') {
            $query = $query->orderBy('userRole.firstName', $direction);
        }

        if ($attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if ($attributes->name) {
            $query = $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
        }

        if ($attributes->createdBy) {
            $query = $query->where('documentAuditTrails.createdBy',  $attributes->createdBy);
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getDocumentAuditTrailsCount($attributes)
    {
        $query = DocumentAuditTrails::query()
            ->join('documents', 'documentAuditTrails.documentId', '=', 'documents.id')
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id');

        if ($attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if ($attributes->name) {
            $query = $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
        }

        if ($attributes->createdBy) {
            $query = $query->where('documentAuditTrails.createdBy',  $attributes->createdBy);
        }

        $count = $query->count();
        return $count;
    }

    public function saveDocumentAuditTrail($request)
    {
        $data = [
            'documentId' => $request->documentId,
            'createdDate' =>  Carbon::now(),
            'operationName' => $request->operationName,
        ];
        $result = DocumentAuditTrails::create($data);
        return $result;
    }

    public function parseEnum(string $value)
    {
        $data =   DocumentOperationEnum::cases($value);
        return $data;
    }

    public function updateDocument($request, $id)
    {
        $model = $this->model->find($id);
        $model->name = $request->name;
        $model->description = $request->description;
        $model->categoryId = $request->categoryId;
        $metaDatas = $request->documentMetaDatas;
        $saved = $model->save();
        $this->resetModel();
        $result = $this->parseResult($model);
        $documentMetadatas = DocumentMetaDatas::where('documentId', '=', $id)->get('id');
        DocumentMetaDatas::destroy($documentMetadatas);

        foreach ($metaDatas as $metaTag) {
            DocumentMetaDatas::create(array(
                'documentId' =>  $id,
                'metatag' =>  $metaTag['metatag'],
            ));
        }

        if (!$saved) {
            throw new RepositoryException('Error in saving data.');
        }
        return $result;
    }
}


