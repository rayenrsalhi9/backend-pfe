<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentMetaDatas;
use App\Models\DocumentAuditTrails;
use App\Models\DocumentOperationEnum;
use App\Models\Documents;
use App\Models\DocumentUserPermissions;
use App\Models\UserNotifications;
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

        $selectColumns = ['documents.id', 'documents.name', 'documents.url', 'documents.createdDate', 'documents.description', 'categories.id as categoryId', 'categories.name as categoryName',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName")
        ];
    
        if ($includeCreatorEmail) {
            $selectColumns[] = 'users.email as createdByEmail';
        }

        $query = Documents::select($selectColumns)
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id');

        $bindings = [$userId];
        $isAllowDownloadSql = "(CASE WHEN EXISTS (
            SELECT 1 FROM documentUserPermissions
            WHERE documentUserPermissions.documentId = documents.id
            AND documentUserPermissions.userId = ?
            AND documentUserPermissions.isAllowDownload = 1
        ) THEN 1 ELSE 0 END) as isAllowDownload";

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
            $query = $query->where(function ($q) use ($attributes) {
                $q->where('documents.name', 'like', '%' . $attributes->name . '%')
                  ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
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

            $assignedUserIds = array();

            $documentUserPermissions = json_decode($request->documentUserPermissions);
            if (is_array($documentUserPermissions)) {
                foreach ($documentUserPermissions as $docuemntUser) {
                    DocumentUserPermissions::create([
                        'documentId' => $result->id,
                        'isAllowDownload' => $docuemntUser->isAllowDownload ?? false,
                        'userId' => $docuemntUser->userId,
                    ]);

                    $assignedUserIds[] = $docuemntUser->userId;
                }
            }

            if (!empty($assignedUserIds)) {
                DocumentAuditTrails::create([
                    'documentId' => $result->id,
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => implode(',', $assignedUserIds)
                ]);
            }

            foreach ($assignedUserIds as $userId) {
                UserNotifications::create([
                    'documentId' => $result->id,
                    'userId' => $userId,
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

            $requestedUserPermissions = $request->input('documentUserPermissions', []);
            if (!is_array($requestedUserPermissions)) { $requestedUserPermissions = []; }

            $requestedUserIds = array_column($requestedUserPermissions, 'userId');

            $existingUserPermissions = DocumentUserPermissions::where('documentId', '=', $id)->get();
            $existingUserIds = $existingUserPermissions->pluck('userId')->toArray();

            $userIdsToRemove = array_diff($existingUserIds, $requestedUserIds);
            $userIdsToAdd = array_diff($requestedUserIds, $existingUserIds);

            if (!empty($userIdsToRemove)) {
                DocumentUserPermissions::where('documentId', '=', $id)
                    ->whereIn('userId', $userIdsToRemove)
                    ->delete();

                DocumentAuditTrails::create([
                    'documentId' => $id,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Remove_Permission->value,
                    'assignToUserId' => implode(',', $userIdsToRemove)
                ]);
            }

            if (empty($requestedUserIds)) {
                UserNotifications::where('documentId', $id)
                    ->where('type', 'document')
                    ->delete();
            } else {
                UserNotifications::where('documentId', $id)
                    ->where('type', 'document')
                    ->whereNotIn('userId', $requestedUserIds)
                    ->delete();
            }

            $userIdsToKeep = array_intersect($existingUserIds, $requestedUserIds);
            if (!empty($userIdsToKeep)) {
                $requestByUserId = array_column($requestedUserPermissions, null, 'userId');
                foreach ($userIdsToKeep as $keepUserId) {
                    $newDownloadFlag = $requestByUserId[$keepUserId]['isAllowDownload'] ?? false;
                    DocumentUserPermissions::where('documentId', $id)
                        ->where('userId', $keepUserId)
                        ->update(['isAllowDownload' => $newDownloadFlag]);
                }
            }

            if (!empty($userIdsToAdd)) {
                foreach ($requestedUserPermissions as $userPermission) {
                    if (!in_array($userPermission['userId'], $userIdsToAdd)) {
                        continue;
                    }

                    DocumentUserPermissions::create([
                        'documentId' => $id,
                        'userId' => $userPermission['userId'],
                        'isAllowDownload' => $userPermission['isAllowDownload'] ?? false,
                    ]);

                    UserNotifications::create([
                        'documentId' => $id,
                        'userId' => $userPermission['userId'],
                        'type' => 'document'
                    ]);
                }

                DocumentAuditTrails::create([
                    'documentId' => $id,
                    'createdDate' => Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => implode(',', $userIdsToAdd)
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

        $isAllowDownloadSql = "(CASE WHEN EXISTS (
            SELECT 1 FROM documentUserPermissions
            WHERE documentUserPermissions.documentId = documents.id
            AND documentUserPermissions.userId = ?
            AND documentUserPermissions.isAllowDownload = 1
        ) THEN 1 ELSE 0 END) as isAllowDownload";

        $isAllowBindings = [$userId];

        $selectColumns = [
            'documents.id', 'documents.name', 'documents.url', 'documents.createdDate', 'documents.description', 'categories.id as categoryId', 'categories.name as categoryName',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName"),
        ];
        
        if ($includeCreatorEmail) {
            $selectColumns[] = 'users.email as createdByEmail';
        }
        
        $query = Documents::select($selectColumns)
            ->selectRaw($isAllowDownloadSql, $isAllowBindings)
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId);
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
            $query = $query->where(function ($q) use ($attributes) {
                $q->where('documents.name', 'like', '%' . $attributes->name . '%')
                  ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
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

    public function assignedDocumentsCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Documents::query()
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId);
                });
            });

        if (property_exists($attributes, 'categoryId') && $attributes->categoryId) {
            $query = $query->where('categoryId', $attributes->categoryId);
        }

        if (property_exists($attributes, 'name') && $attributes->name) {
            $query = $query->where(function ($q) use ($attributes) {
                $q->where('documents.name', 'like', '%' . $attributes->name . '%')
                  ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
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

        $count = $query->count();
        return $count;
    }

    public function getDocumentByCategory()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');

        $query = Documents::select(['documents.categoryId', 'categories.name as categoryName',  DB::raw('count(*) as documentCount')])
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId);
                });
            });

        $results =  $query->groupBy('documents.categoryId', 'categories.name')->get();

        return $results;
    }

    public function getDocumentbyId($id)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Documents::select(['documents.*'])
            ->where('documents.id',  '=', $id)
            ->where(function ($query) use ($userId, $id) {
                $query->whereExists(function ($query) use ($userId, $id) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->where('documentUserPermissions.documentId', '=', $id)
                        ->where('documentUserPermissions.userId', '=', $userId);
                });
            });

        $document = $query->first();

        if ($document == null) {
            return null;
        }

        $userHasDownloadPerm = DocumentUserPermissions::where('documentUserPermissions.documentId', '=', $id)
            ->where('documentUserPermissions.userId', '=', $userId)
            ->where('documentUserPermissions.isAllowDownload', '=', true)
            ->exists();

        $document['isAllowDownload'] = $userHasDownloadPerm;
        return $document;
    }
}
