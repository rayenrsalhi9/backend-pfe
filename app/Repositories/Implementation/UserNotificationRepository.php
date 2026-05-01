<?php

namespace App\Repositories\Implementation;

use App\Models\UserNotifications;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Exceptions\RepositoryException;
use App\Traits\CacheableTrait;

//use Your Model

/**
 * Class UserRepository.
 */
class UserNotificationRepository extends BaseRepository implements UserNotificationRepositoryInterface
{
    use CacheableTrait;

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
        return UserNotifications::class;
    }


    public function getTop10Notification()
    {
        try {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            if ($userId == null) {
                return [];
            }

            $cacheKey = $this->getCacheKey('notifications', 'top10', $userId);
            $ttl = $this->getCacheTtl('notifications');

            return $this->cacheRemember($cacheKey, 'notifications', $ttl, function () use ($userId) {
                $results = UserNotifications::where('userId', '=', $userId)
                    ->orderBy('isRead', 'DESC')
                    ->orderBy('createdDate', 'DESC')
                    ->with('user')
                    ->with(['documents' => function ($query) {
                        $query->withoutGlobalScope('isDeleted');
                    }])
                    ->take(10)
                    ->get();

                foreach ($results as $notification) {
                    if ($notification->documentId && $notification->documents && $notification->documents->isDeleted) {
                        $notification->message = $notification->message . ' [Event deleted]';
                        $notification->documentId = null;
                    }
                }

                return $results;
            });
        } catch (\Exception $e) {
            \Log::error('getTop10Notification error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserNotificaions($attributes)
    {
        try {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            if ($userId == null) {
                throw new RepositoryException('User does not exist.');
            }
            $query = UserNotifications::where('userId', '=', $userId)
                ->with('user')
                ->with(['documents' => function ($query) {
                    $query->withoutGlobalScope('isDeleted');
                }]);

            $orderByRaw = $attributes->orderBy ?? 'createdDate desc';
            $orderByArray = explode(' ', $orderByRaw);
            $orderBy = $orderByArray[0] ?? 'createdDate';
            $directionRaw = $orderByArray[1] ?? 'desc';
            $direction = in_array(strtolower($directionRaw), ['asc', 'desc']) ? strtolower($directionRaw) : 'desc';

            if ($orderBy == 'message') {
                $query = $query->orderBy('message', $direction);
            } elseif ($orderBy == 'createdDate') {
                $query = $query->orderBy('createdDate', $direction);
            }

            $name = $attributes->name ?? null;
            if ($name) {
                $query = $query->where('message', 'like', '%' . $name . '%');
            }

            $skip = is_numeric($attributes->skip ?? null) ? (int)$attributes->skip : 0;
            $pageSize = is_numeric($attributes->pageSize ?? null) ? (int)$attributes->pageSize : 10;
            $results = $query->skip($skip)->take($pageSize)->get();

            foreach ($results as $notification) {
                if ($notification->documentId && $notification->documents && $notification->documents->isDeleted) {
                    $notification->message = $notification->message . ' [Event deleted]';
                    $notification->documentId = null;
                }
            }

            return $results;
        } catch (RepositoryException $e) {
            \Log::error('getUserNotificaions error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            \Log::error('getUserNotificaions error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserNotificaionCount($attributes)
    {
        try {
            $userId = Auth::parseToken()->getPayload()->get('userId');
            if ($userId == null) {
                throw new RepositoryException('User does not exist.');
            }
            $query = UserNotifications::query()
                ->where('userId', '=', $userId);

            $name = $attributes->name ?? null;
            if ($name) {
                $query = $query->where('message', 'like', '%' . $name . '%');
            }

            $count = $query->count();
            return $count;
        } catch (RepositoryException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('getUserNotificaionCount error: ' . $e->getMessage());
            return 0;
        }
    }

    public function markAsRead($request)
    {
        $model = $this->model->find($request->id);
        $model->isRead = true;
        $saved = $model->save();
        $this->resetModel();
        $result = $this->parseResult($model);

        if (!$saved) {
            throw new RepositoryException('Error in saving data.');
        }

        $this->flushCacheTag('notifications');
        return $result;
    }

    public function markAllAsRead($options = [])
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            throw new RepositoryException('User does not exist.');
        }

        $query = UserNotifications::where('userId', $userId);

        if (array_key_exists('excludeTypes', $options)) {
            $excludeTypes = $options['excludeTypes'];
            if (is_string($excludeTypes)) {
                $excludeTypes = array_map('trim', explode(',', $excludeTypes));
            } elseif (is_array($excludeTypes)) {
                $excludeTypes = array_map('trim', $excludeTypes);
            }
            if (!is_array($excludeTypes)) {
                throw new RepositoryException('Invalid excludeTypes value. Must be an array or comma-separated string.');
            }
            $excludeTypes = array_filter($excludeTypes, function ($v) {
                return $v !== null && $v !== '';
            });
            $excludeTypes = array_values($excludeTypes);
            if (!empty($excludeTypes) && count(array_filter($excludeTypes, 'is_string')) !== count($excludeTypes)) {
                throw new RepositoryException('Invalid excludeTypes value. All values must be strings.');
            }
            if (!empty($excludeTypes)) {
                $query->whereNotIn('type', $excludeTypes);
            }
        }

        $query->where(function($q) {
            $q->whereNull('isRead')->orWhere('isRead', false);
        });

        $query->update(['isRead' => true]);

        $this->flushCacheTag('notifications');

        return;
    }

    public function markAsReadByDocumentId($documentId)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            throw new RepositoryException('User does not exist.');
        }

        $userNotifications = UserNotifications::where('userId', '=', $userId)
            ->where('documentId', '=', $documentId)->get();

        foreach ($userNotifications as $userNotification) {
            $userNotification->isRead = true;
            $userNotification->save();
        }

        $this->flushCacheTag('notifications');
        return;
    }
}
