<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentAuditTrails;
use App\Models\DocumentUserPermissions;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentPermissionRepositoryInterface;
use Carbon\Carbon;
use App\Models\DocumentOperationEnum;
use App\Models\Documents;
use App\Models\UserNotifications;
use App\Models\UserRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class DocumentPermissionRepository extends BaseRepository implements DocumentPermissionRepositoryInterface
{

    /**
     * @var Model
     */
    protected $model;
    private $list;


    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return DocumentUserPermissions::class;
    }

    public function getDocumentPermissionList($id)
    {
        return DocumentUserPermissions::where('documentId', '=', $id)
            ->with(['user' => function ($query) {
                $query->select('id', 'username', 'firstName', 'lastName', 'email');
            }])
            ->get()
            ->map(function ($item) {
                $item->type = 'User';
                return $item;
            });
    }

    public function addDocumentUserPermission($request)
    {
        try {
            DB::beginTransaction();

            $documentUserPermissions = $request['documentUserPermissions'];
            $permissionsByDocument = [];

            foreach ($documentUserPermissions as $docuemntUser) {
                $documentId = $docuemntUser['documentId'];

                $model = DocumentUserPermissions::create([
                    'documentId' => $documentId,
                    'isAllowDownload' => $docuemntUser['isAllowDownload'],
                    'userId' => $docuemntUser['userId'],
                ]);

                $permissionsByDocument[$documentId][] = $docuemntUser['userId'];

                UserNotifications::create([
                    'documentId' => $documentId,
                    'userId' => $docuemntUser['userId'],
                    'type' => 'document'
                ]);
            }

            foreach ($permissionsByDocument as $documentId => $userIds) {
                DocumentAuditTrails::create([
                    'documentId' => $documentId,
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => implode(',', $userIds)
                ]);
            }

            DB::commit();
            $this->resetModel();
            $result = $this->parseResult($model);
            return $result->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function deleteDocumentUserPermission($id)
    {
        $model = DocumentUserPermissions::find($id);
        if (!is_null($model)) {
            DocumentAuditTrails::create([
                'documentId' => $model->documentId,
                'createdDate' =>  Carbon::now(),
                'operationName' => DocumentOperationEnum::Remove_Permission->value,
                'assignToUserId' => $model->userId
            ]);

            UserNotifications::where('documentId', $model->documentId)
                ->where('userId', $model->userId)
                ->where('type', 'document')
                ->delete();
        }
        return DocumentUserPermissions::destroy($id);
    }

    public function getIsDownloadFlag($id)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Documents::where('documents.id', '=', $id)
            ->where(function ($query) use ($userId) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
                        ->where('documentUserPermissions.isAllowDownload', '=', true);
                });
            });

        $count = $query->count();
        return $count > 0 ? true : false;
    }
}
