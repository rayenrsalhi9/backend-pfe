<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentAuditTrails;
use App\Models\DocumentRolePermissions;
use App\Models\DocumentUserPermissions;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentPermissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DocumentOperationEnum;
use App\Models\Documents;
use App\Models\UserNotifications;
use App\Models\UserRoles;
use Illuminate\Support\Facades\Auth;


class DocumentPermissionRepository extends BaseRepository implements DocumentPermissionRepositoryInterface
{

    /**
     * @var Model
     */
    protected $model;
    protected $startDate;
    protected $endDate;
    private $list;


    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return DocumentRolePermissions::class;
    }

    public function getDocumentPermissionList($id)
    {
        $documentRolePermission = DocumentRolePermissions::where('documentId', '=', $id)->with('role')->get();
        $documentRolePermissionList = $documentRolePermission->map(function ($item) {
            $item->type = 'Role';
            return $item;
        });
        $documentUserPermission = DocumentUserPermissions::where('documentId', '=', $id)
            ->with(['user' => function ($query) {
                $query->select('id', 'username', 'firstName', 'lastName', 'email');
            }])->get();
        $documentUserPermissionList = $documentUserPermission->map(function ($item) {
            $item->type = 'User';
            return $item;
        });
        $result = $documentRolePermissionList->concat($documentUserPermissionList);
        return $result;
    }

    public function addDocumentRolePermission($request)
    {
        try {
            DB::beginTransaction();

            $documentRolePermissions = $request['documentRolePermissions'];
            $rolePermissionsArray = array();
            foreach ($documentRolePermissions as $docuemntrole) {
                if ($docuemntrole['isTimeBound']) {
                    $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole['startDate'])));
                    $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole['endDate'])));
                    $this->startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                    $this->endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                }

                $model = DocumentRolePermissions::create([
                    'documentId' => $docuemntrole['documentId'],
                    'endDate' => $this->endDate  ?? '',
                    'isAllowDownload' => $docuemntrole['isAllowDownload'],
                    'isTimeBound' => $docuemntrole['isTimeBound'],
                    'roleId' => $docuemntrole['roleId'],
                    'startDate' => $this->startDate ?? ''
                ]);

                DocumentAuditTrails::create([
                    'documentId' => $docuemntrole['documentId'],
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToRoleId' => $docuemntrole['roleId']
                ]);

                $userIds = UserRoles::select('userId')
                    ->where('roleId', $docuemntrole['roleId'])
                    ->get();

                foreach ($userIds as $userIdObject) {
                    array_push($rolePermissionsArray, [
                        'documentId' => $docuemntrole['documentId'],
                        'userId' => $userIdObject['userId']
                    ]);
                }
            }

            $rolePermissions = array_unique($rolePermissionsArray, SORT_REGULAR);
            foreach ($rolePermissions as $rolePermission) {
                UserNotifications::create([
                    'documentId' => $rolePermission['documentId'],
                    'userId' => $rolePermission['userId']
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

    public function addDocumentUserPermission($request)
    {
        try {
            DB::beginTransaction();

            $documentUserPermissions = $request['documentUserPermissions'];
            foreach ($documentUserPermissions as $docuemntUser) {
                if ($docuemntUser['isTimeBound']) {
                    $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser['startDate'])));
                    $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser['endDate'])));
                    $this->startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                    $this->endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                }

                $model = DocumentUserPermissions::create([
                    'documentId' => $docuemntUser['documentId'],
                    'endDate' => $this->endDate  ?? '',
                    'isAllowDownload' => $docuemntUser['isAllowDownload'],
                    'isTimeBound' => $docuemntUser['isTimeBound'],
                    'userId' => $docuemntUser['userId'],
                    'startDate' => $this->startDate ?? ''
                ]);

                DocumentAuditTrails::create([
                    'documentId' => $docuemntUser['documentId'],
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => $docuemntUser['userId']
                ]);

                UserNotifications::create([
                    'documentId' => $docuemntUser['documentId'],
                    'userId' => $docuemntUser['userId']
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
        }
        return DocumentUserPermissions::destroy($id);
    }

    public function deleteDocumentRolePermission($id)
    {
        $model = DocumentRolePermissions::find($id);
        if (!is_null($model)) {
            DocumentAuditTrails::create([
                'documentId' => $model->documentId,
                'createdDate' =>  Carbon::now(),
                'operationName' => DocumentOperationEnum::Remove_Permission->value,
                'assignToRoleId' => $model->roleId
            ]);
        }
        return DocumentRolePermissions::destroy($id);
    }

    public function multipleDocumentsToUsersAndRoles($request)
    {
        try {
            DB::beginTransaction();

            $permissionsArray = array();
            foreach ($request['documents'] as $document) {
                if ($request['isTimeBound']) {
                    $startdate = date('Y-m-d', strtotime(str_replace('/', '-', $request['startDate'])));
                    $endDate = date('Y-m-d', strtotime(str_replace('/', '-', $request['endDate'])));
                    if ($request['roles']) {
                        foreach ($request['roles'] as $role) {
                            DocumentRolePermissions::create([
                                'documentId' => $document,
                                'endDate' => $endDate ?? '',
                                'isAllowDownload' => $request['isAllowDownload'],
                                'isTimeBound' => $request['isTimeBound'],
                                'roleId' => $role,
                                'startDate' => $startdate ?? ''
                            ]);

                            $userIds = UserRoles::select('userId')
                                ->where('roleId', $role)
                                ->get();


                            foreach ($userIds as $userIdObject) {
                                array_push($permissionsArray, [
                                    'documentId' => $document,
                                    'userId' => $userIdObject['userId']
                                ]);
                            }
                        }
                    }
                    if ($request['users']) {
                        foreach ($request['users'] as $user) {
                            DocumentUserPermissions::create([
                                'documentId' => $document,
                                'endDate' => $endDate ?? '',
                                'isAllowDownload' => $request['isAllowDownload'],
                                'isTimeBound' => $request['isTimeBound'],
                                'userId' => $user,
                                'startDate' => $startdate ?? ''
                            ]);

                            array_push($permissionsArray, [
                                'documentId' => $document,
                                'userId' => $user
                            ]);
                        }
                    }
                } else {
                    if ($request['roles']) {
                        foreach ($request['roles'] as $role) {
                            DocumentRolePermissions::create([
                                'documentId' => $document,
                                'isAllowDownload' => $request['isAllowDownload'],
                                'isTimeBound' => $request['isTimeBound'],
                                'roleId' => $role,
                            ]);

                            $userIds = UserRoles::select('userId')
                                ->where('roleId', $role)
                                ->get();

                            $userIds = UserRoles::select('userId')
                                ->where('roleId', $role)
                                ->get();


                            foreach ($userIds as $userIdObject) {
                                array_push($permissionsArray, [
                                    'documentId' => $document,
                                    'userId' => $userIdObject['userId']
                                ]);
                            }
                        }
                    }
                    if ($request['users']) {
                        foreach ($request['users'] as $user) {
                            DocumentUserPermissions::create([
                                'documentId' => $document,
                                'isAllowDownload' => $request['isAllowDownload'],
                                'isTimeBound' => $request['isTimeBound'],
                                'userId' => $user,
                            ]);

                            array_push($permissionsArray, [
                                'documentId' => $document,
                                'userId' => $user
                            ]);
                        }
                    }
                }
            }

            $permissions = array_unique($permissionsArray, SORT_REGULAR);
            foreach ($permissions as $permission) {
                UserNotifications::create([
                    'documentId' => $permission['documentId'],
                    'userId' => $permission['userId']
                ]);
            }
            DB::commit();

            return [];
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function getIsDownloadFlag($id, $isPermission)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $query = Documents::where('documents.id',  '=', $id)
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
                        ->where('documentUserPermissions.isAllowDownload', '=', true)
                        ->where(function ($query) {
                            $query->where('documentUserPermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentUserPermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentUserPermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentUserPermissions.endDate', '>=', $endDate);
                                });
                        });
                })->orWhereExists(function ($query) use ($userRoles) {
                    $query->select(DB::raw(1))
                        ->from('documentRolePermissions')
                        ->whereRaw('documentRolePermissions.documentId = documents.id')
                        ->where('documentRolePermissions.isAllowDownload', '=', true)
                        ->whereIn('documentRolePermissions.roleId', $userRoles)
                        ->where(function ($query) {
                            $query->where('documentRolePermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentRolePermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentRolePermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentRolePermissions.endDate', '>=', $endDate);
                                });
                        });
                });
            });


        $count = $query->count();
        return $count > 0 ? true : false;
    }
}
