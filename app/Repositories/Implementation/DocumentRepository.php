<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentMetaDatas;
use App\Models\DocumentAuditTrails;
use App\Models\DocumentOperationEnum;
use App\Models\DocumentRolePermissions;
use App\Models\Documents;
use App\Models\DocumentUserPermissions;
use App\Models\UserNotifications;
use App\Models\UserRoles;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use App\Traits\CacheableTrait;
//use Your Model

/**
 * Class UserRepository.
 */
class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    use CacheableTrait;

    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor..
     *
     * @param Model $model
     */


    public static function model()
    {
        return Documents::class;
    }

    public function getDocuments($attributes, $includeCreatorEmail = false)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $roleIds = $userRoles->pluck('roleId')->toArray();

        $selectColumns = ['documents.id', 'documents.name', 'documents.url', 'documents.createdDate', 'documents.description', 'categories.id as categoryId', 'categories.name as categoryName',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName")
        ];
    
        if ($includeCreatorEmail) {
            $selectColumns[] = 'users.email as createdByEmail';
        }

        $query = Documents::select($selectColumns)
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id');

        $isAllowDownloadSql = "(CASE WHEN EXISTS (
            SELECT 1 FROM documentUserPermissions
            WHERE documentUserPermissions.documentId = documents.id
            AND documentUserPermissions.userId = ?
            AND documentUserPermissions.isAllowDownload = 1
        ) OR EXISTS (
            SELECT 1 FROM documentRolePermissions
            WHERE documentRolePermissions.documentId = documents.id
            AND documentRolePermissions.isAllowDownload = 1
            AND documentRolePermissions.roleId IN (";

        if (!empty($roleIds)) {
            $isAllowDownloadSql .= rtrim(str_repeat('?,', count($roleIds)), ',');
            $bindings = array_merge([$userId], $roleIds);
        } else {
            $isAllowDownloadSql .= '?';
            $bindings = [$userId, 'none'];
        }

        $isAllowDownloadSql .= ")) THEN 1 ELSE 0 END) as isAllowDownload";

        $query->selectRaw($isAllowDownloadSql, $bindings);

        $orderByArray = explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'name') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'createdDate') {
            $query = $query->orderBy('documents.createdDate', $direction);
        } else if ($orderBy == 'createdBy') {
            $query = $query->orderBy('users.firstName', $direction);
        }

        if (property_exists($attributes, 'categoryId') && $attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if (property_exists($attributes, 'name') && $attributes->name) {
            $query = $query->where(function ($q) use ($attributes) {
                $q->where('documents.name', 'like', '%' . $attributes->name . '%')
                  ->orWhere('documents.description', 'like', '%' . $attributes->name . '%');
            });
        }

        if (property_exists($attributes, 'metaTags') && $attributes->metaTags) {
            $metaTags = $attributes->metaTags;
            $query = $query->whereExists(function ($query) use ($metaTags) {
                $query->select(DB::raw(1))
                    ->from('documentMetaDatas')
                    ->whereRaw('documentMetaDatas.documentId = documents.id')
                    ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
            });
        }

        if (property_exists($attributes, 'createDateString') && $attributes->createDateString) {

            $startDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC');
            $endDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query = $query->whereBetween('documents.createdDate', [$startDate, $endDate]);
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getDocumentsCount($attributes)
    {
        $query = Documents::query()
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id');

        if (property_exists($attributes, 'categoryId') && $attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if (property_exists($attributes, 'name') && $attributes->name) {
            $query = $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
        }

        if (property_exists($attributes, 'metaTags') && $attributes->metaTags) {
            $metaTags = $attributes->metaTags;
            $query = $query->whereExists(function ($query) use ($metaTags) {
                $query->select(DB::raw(1))
                    ->from('documentMetaDatas')
                    ->whereRaw('documentMetaDatas.documentId = documents.id')
                    ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
            });
        }

        if (property_exists($attributes, 'createDateString') && $attributes->createDateString) {
            $date = date('Y-m-d', strtotime(str_replace('/', '-', $attributes->createDateString)));

            $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

            $query = $query->whereDate('documents.createdDate', '>=', $startDate)
                ->whereDate('documents.createdDate', '<=', $endDate);
        }

        $count = $query->count();
        return $count;
    }


    public function saveDocument($request, $path)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->newInstance($request);
            $model->url = $path;
            $model->categoryId = $request->categoryId;
            $model->name = $request->name;
            $model->extension = $request->extension;
            $model->description = $request->description;
            $metaDatas = $request->documentMetaDatas;
            $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            $decodedMetaDatas = json_decode($metaDatas);
            if (is_array($decodedMetaDatas)) {
                foreach ($decodedMetaDatas as $metaTag) {
                    DocumentMetaDatas::create(array(
                        'documentId' =>   $result->id,
                        'metatag' =>  $metaTag->metatag,
                    ));
                }
            }

            $documentRolePermissions = json_decode($request->documentRolePermissions);
            $rolePermissionsArray = array();
            $assignedRoleIds = array();
            $assignedUserIds = array();

            if (is_array($documentRolePermissions)) {
                foreach ($documentRolePermissions as $docuemntrole) {
                    $startDate = '';
                    $endDate = '';
                    $isTimeBound = $docuemntrole->isTimeBound ?? false;
                    if ($isTimeBound) {
                        $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole->startDate ?? '')));
                        $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole->endDate ?? '')));
                        $startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                        $endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                    }

                    DocumentRolePermissions::create([
                        'documentId' => $result->id,
                        'endDate' => $endDate  ?? '',
                        'isAllowDownload' => $docuemntrole->isAllowDownload ?? false,
                        'isTimeBound' => $isTimeBound,
                        'roleId' => $docuemntrole->roleId,
                        'startDate' => $startDate ?? ''
                    ]);

                    $assignedRoleIds[] = $docuemntrole->roleId;

                    $userIds = UserRoles::select('userId')
                        ->where('roleId', $docuemntrole->roleId)
                        ->get();

                    foreach ($userIds as $userIdObject) {
                        array_push($rolePermissionsArray, [
                            'documentId' => $result->id,
                            'userId' => $userIdObject->userId
                        ]);
                    }
                }
            }

            $documentUserPermissions = json_decode($request->documentUserPermissions);
            if (is_array($documentUserPermissions)) {
                foreach ($documentUserPermissions as $docuemntUser) {
                    $startDate = '';
                    $endDate = '';
                    $isTimeBound = $docuemntUser->isTimeBound ?? false;
                    if ($isTimeBound) {
                        $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser->startDate ?? '')));
                        $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser->endDate ?? '')));
                        $startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                        $endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                    }

                    DocumentUserPermissions::create([
                        'documentId' => $result->id,
                        'endDate' => $endDate  ?? '',
                        'isAllowDownload' => $docuemntUser->isAllowDownload ?? false,
                        'isTimeBound' => $isTimeBound,
                        'userId' => $docuemntUser->userId,
                        'startDate' => $startDate ?? ''
                    ]);

                    $assignedUserIds[] = $docuemntUser->userId;

                    array_push($rolePermissionsArray, [
                        'documentId' => $result->id,
                        'userId' => $docuemntUser->userId
                    ]);
                }
            }

            DocumentAuditTrails::create([
                'documentId' => $result->id,
                'createdDate' =>  Carbon::now(),
                'operationName' => DocumentOperationEnum::Add_Permission->value,
                'assignToRoleId' => !empty($assignedRoleIds) ? implode(',', $assignedRoleIds) : null,
                'assignToUserId' => !empty($assignedUserIds) ? implode(',', $assignedUserIds) : null
            ]);


            $rolePermissions = array_unique($rolePermissionsArray, SORT_REGULAR);
            foreach ($rolePermissions as $rolePermission) {
                UserNotifications::create([
                    'documentId' => $result->id,
                    'userId' => $rolePermission['userId'],
                    'type' => 'document'
                ]);
            }

            $userId = Auth::parseToken()->getPayload()->get('userId');

            $array = is_array($documentUserPermissions) ? array_filter($documentUserPermissions, function ($item) use ($userId) {
                return $item->userId == $userId;
            }) : [];

            if (count($array) == 0) {
                DocumentUserPermissions::create(array(
                    'documentId' =>   $result->id,
                    'userId' =>  $userId,
                    'isAllowDownload' => true
                ));
            }
            DB::commit();
            return response()->json((string)$result->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function updateDocument($request, $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->find($id);

            $model->name = $request->name;
            $model->description = $request->description;
            $model->categoryId = $request->categoryId;
            $metaDatas = $request->documentMetaDatas;
            $model->save();
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

            $rolePermissionsArray = array();
            $assignedRoleIds = array();
            $assignedUserIds = array();

            $documentRolePermissions = DocumentRolePermissions::where('documentId', '=', $id)->get('id');
            DocumentRolePermissions::destroy($documentRolePermissions);

            if ($request->has('documentRolePermissions') && is_array($request->documentRolePermissions)) {
                foreach ($request->documentRolePermissions as $rolePermission) {
                    $startDate = '';
                    $endDate = '';
                    $isTimeBound = $rolePermission['isTimeBound'] ?? false;
                    if ($isTimeBound) {
                        $startDate = $rolePermission['startDate'] ?? '';
                        $endDate = $rolePermission['endDate'] ?? '';
                    }

                    DocumentRolePermissions::create([
                        'documentId' => $id,
                        'roleId' => $rolePermission['roleId'],
                        'isTimeBound' => $isTimeBound,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'isAllowDownload' => $rolePermission['isAllowDownload'] ?? false,
                    ]);

                    $assignedRoleIds[] = $rolePermission['roleId'];

                    $userIds = UserRoles::select('userId')
                        ->where('roleId', $rolePermission['roleId'])
                        ->get();

                    foreach ($userIds as $userIdObject) {
                        array_push($rolePermissionsArray, [
                            'documentId' => $id,
                            'userId' => $userIdObject->userId
                        ]);
                    }
                }
            }

            $documentUserPermissions = DocumentUserPermissions::where('documentId', '=', $id)->get('id');
            DocumentUserPermissions::destroy($documentUserPermissions);

            if ($request->has('documentUserPermissions') && is_array($request->documentUserPermissions)) {
                foreach ($request->documentUserPermissions as $userPermission) {
                    $startDate = '';
                    $endDate = '';
                    $isTimeBound = $userPermission['isTimeBound'] ?? false;
                    if ($isTimeBound) {
                        $startDate = $userPermission['startDate'] ?? '';
                        $endDate = $userPermission['endDate'] ?? '';
                    }

                    DocumentUserPermissions::create([
                        'documentId' => $id,
                        'userId' => $userPermission['userId'],
                        'isTimeBound' => $isTimeBound,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'isAllowDownload' => $userPermission['isAllowDownload'] ?? false,
                    ]);

                    $assignedUserIds[] = $userPermission['userId'];

                    array_push($rolePermissionsArray, [
                        'documentId' => $id,
                        'userId' => $userPermission['userId']
                    ]);
                }
            }

            if (!empty($assignedRoleIds)) {
                DocumentAuditTrails::create([
                    'documentId' => $id,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToRoleId' => implode(',', $assignedRoleIds)
                ]);
            }

            if (!empty($assignedUserIds)) {
                DocumentAuditTrails::create([
                    'documentId' => $id,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => implode(',', $assignedUserIds)
                ]);
            }

            UserNotifications::where('documentId', '=', $id)->where('type', 'document')->delete();

            $rolePermissions = array_unique($rolePermissionsArray, SORT_REGULAR);
            foreach ($rolePermissions as $rolePermission) {
                UserNotifications::create([
                    'documentId' => $id,
                    'userId' => $rolePermission['userId'],
                    'type' => 'document'
                ]);
            }

            $currentUserId = Auth::parseToken()->getPayload()->get('userId');

            $currentUserHasPermission = DocumentUserPermissions::where('documentId', '=', $id)
                ->where('userId', '=', $currentUserId)
                ->exists();

            if (!$currentUserHasPermission) {
                DocumentUserPermissions::create([
                    'documentId' => $id,
                    'userId' => $currentUserId,
                    'isAllowDownload' => true
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }

        try {
            $this->flushCacheTag('documents');
        } catch (\Exception $e) {
            \Log::error('Cache flush failed: ' . $e->getMessage());
        }

        return $result;
    }

    public function assignedDocuments($attributes, $includeCreatorEmail = false)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
            
        $roleIds = $userRoles->pluck('roleId')->toArray();
        $roleIdList = !empty($roleIds) ? implode(',', array_map(function ($id) {
            return "'$id'";
        }, $roleIds)) : "'none'";

        $selectColumns = [
            'documents.id', 'documents.name', 'documents.url', 'documents.createdDate', 'documents.description', 'categories.id as categoryId', 'categories.name as categoryName',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName"),
            DB::raw("(SELECT max(documentUserPermissions.endDate) FROM documentUserPermissions
                     WHERE documentUserPermissions.documentId = documents.id and documentUserPermissions.isTimeBound =1
                     GROUP BY documentUserPermissions.documentId) as maxUserPermissionEndDate"),
            DB::raw("(SELECT max(documentRolePermissions.endDate) FROM documentRolePermissions
                     WHERE documentRolePermissions.documentId = documents.id and documentRolePermissions.isTimeBound =1
                     GROUP BY documentRolePermissions.documentId) as maxRolePermissionEndDate"),
            DB::raw("(CASE WHEN EXISTS (
                SELECT 1 FROM documentUserPermissions
                WHERE documentUserPermissions.documentId = documents.id
                AND documentUserPermissions.userId = '$userId'
                AND documentUserPermissions.isAllowDownload = 1
            ) OR EXISTS (
                SELECT 1 FROM documentRolePermissions
                WHERE documentRolePermissions.documentId = documents.id
                AND documentRolePermissions.isAllowDownload = 1
                AND documentRolePermissions.roleId IN ($roleIdList)
            ) THEN 1 ELSE 0 END) as isAllowDownload")
        ];
        
        if ($includeCreatorEmail) {
            $selectColumns[] = 'users.email as createdByEmail';
        }
        
        $query = Documents::select($selectColumns)
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
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

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'name') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'createdDate') {
            $query = $query->orderBy('documents.createdDate', $direction);
        } else if ($orderBy == 'createdBy') {
            $query = $query->orderBy('users.firstName', $direction);
        }

        if (property_exists($attributes, 'categoryId') && $attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if (property_exists($attributes, 'name') && $attributes->name) {
            $query = $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
        }

        if (property_exists($attributes, 'metaTags') && $attributes->metaTags) {
            $metaTags = $attributes->metaTags;
            $query = $query->whereExists(function ($query) use ($metaTags) {
                $query->select(DB::raw(1))
                    ->from('documentMetaDatas')
                    ->whereRaw('documentMetaDatas.documentId = documents.id')
                    ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
            });
        }

        if (property_exists($attributes, 'createDateString') && $attributes->createDateString) {

            $startDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC');
            $endDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query = $query->whereBetween('documents.createdDate', [$startDate, $endDate]);
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function assignedDocumentsCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $query = Documents::query()
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
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

        if (property_exists($attributes, 'categoryId') && $attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if (property_exists($attributes, 'name') && $attributes->name) {
            $query = $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
        }

        if (property_exists($attributes, 'metaTags') && $attributes->metaTags) {
            $metaTags = $attributes->metaTags;
            $query = $query->whereExists(function ($query) use ($metaTags) {
                $query->select(DB::raw(1))
                    ->from('documentMetaDatas')
                    ->whereRaw('documentMetaDatas.documentId = documents.id')
                    ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
            });
        }

        $count = $query->count();
        return $count;
    }

    public function getDocumentByCategory()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();

        $query = Documents::select(['documents.categoryId', 'categories.name as categoryName',  DB::raw('count(*) as documentCount')])
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
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

        $results =  $query->groupBy('documents.categoryId', 'categories.name')->get();

        return $results;
    }

    public function getDocumentbyId($id)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $query = Documents::select(['documents.*'])
            ->where('documents.id',  '=', $id)
            ->where(function ($query) use ($userId, $userRoles, $id) {
                $query->whereExists(function ($query) use ($userId, $id) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->where('documentUserPermissions.documentId', '=', $id)
                        ->where('documentUserPermissions.userId', '=', $userId)
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
                })->orWhereExists(function ($query) use ($userRoles, $id) {
                    $query->select(DB::raw(1))
                        ->from('documentRolePermissions')
                        ->where('documentRolePermissions.documentId', '=', $id)
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

        $document = $query->first();

        if ($document == null) {
            return null;
        }

        $docUserPermissionQuery = DocumentUserPermissions::where('documentUserPermissions.documentId',  '=', $id)
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

        $userPermissionCount = $docUserPermissionQuery->count();
        if ($userPermissionCount > 0) {
            $document['isAllowDownload'] = true;
            return $document;
        }

        $docRolePermissionQuery = DocumentRolePermissions::where('documentRolePermissions.documentId',  '=', $id)
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

        $rolePermissionCount = $docRolePermissionQuery->count();
        if ($rolePermissionCount > 0) {
            $document['isAllowDownload'] = true;
            return $document;
        } else {
            $document['isAllowDownload'] = false;
            return $document;
        }
    }
}
