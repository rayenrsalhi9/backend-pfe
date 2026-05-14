<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentAuditTrails;
use App\Models\DocumentOperationEnum;
use App\Models\Documents;
use App\Models\DocumentUserPermissions;
use App\Models\UserNotifications;
use App\Repositories\Contracts\DocumentPermissionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentPermissionRepository extends BaseRepository implements DocumentPermissionRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param  Model  $model
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
            $createdModels = [];

            $seen = [];
            $uniquePermissions = [];
            foreach ($documentUserPermissions as $perm) {
                $key = $perm['documentId'].'|'.$perm['userId'];
                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniquePermissions[] = $perm;
                }
            }

            foreach ($uniquePermissions as $documentUser) {
                $documentId = $documentUser['documentId'];

                $model = DocumentUserPermissions::updateOrCreate(
                    ['documentId' => $documentId, 'userId' => $documentUser['userId']],
                    ['isAllowDownload' => $documentUser['isAllowDownload']]
                );

                $createdModels[] = $model;

                if ($model->wasRecentlyCreated || $model->wasChanged('isAllowDownload')) {
                    if (! in_array($documentUser['userId'], $permissionsByDocument[$documentId] ?? [])) {
                        $permissionsByDocument[$documentId][] = $documentUser['userId'];
                    }

                    UserNotifications::create([
                        'documentId' => $documentId,
                        'userId' => $documentUser['userId'],
                        'type' => 'document',
                    ]);
                }
            }

            foreach ($permissionsByDocument as $documentId => $userIds) {
                DocumentAuditTrails::create([
                    'documentId' => $documentId,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => implode(',', $userIds),
                ]);
            }

            DB::commit();
            $this->resetModel();
            if (empty($createdModels)) {
                return [];
            }

            return collect($createdModels)->map(function ($model) {
                return $this->parseResult($model)->toArray();
            })->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteDocumentUserPermission($id)
    {
        return DB::transaction(function () use ($id) {
            $model = DocumentUserPermissions::find($id);
            if (! is_null($model)) {
                DocumentAuditTrails::create([
                    'documentId' => $model->documentId,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Remove_Permission->value,
                    'assignToUserId' => $model->userId,
                ]);

                UserNotifications::where('documentId', $model->documentId)
                    ->where('userId', $model->userId)
                    ->where('type', 'document')
                    ->delete();
            }

            return DocumentUserPermissions::destroy($id);
        });
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
