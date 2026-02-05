<?php

namespace App\Repositories\Implementation;

use App\Models\UserNotifications;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Exceptions\RepositoryException;

//use Your Model

/**
 * Class UserRepository.
 */
class UserNotificationRepository extends BaseRepository implements UserNotificationRepositoryInterface
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
        return UserNotifications::class;
    }


    public function getTop10Notification()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            return [];
        }

        $query = UserNotifications::select(['userNotifications.*', 'documents.id as documentId', 'documents.name as documentName'])
            ->where('userNotifications.userId', '=', $userId)
            //->where('documents.isDeleted', '=', false)
            ->orderBy('userNotifications.isRead', 'DESC')
            ->orderBy('userNotifications.createdDate', 'DESC')
            ->with('user')
            ->leftJoin('documents', 'userNotifications.documentId', '=', 'documents.id');

        $results = $query->take(10)->get();

        return $results;
    }

    public function getUserNotificaions($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            throw new RepositoryException('User does not exist.');
        }
        $query = UserNotifications::select(['userNotifications.*', 'documents.id as documentId', 'documents.name as documentName'])
            ->where('userNotifications.userId', '=', $userId)
            ->where('documents.isDeleted', '=', false)
            ->with('user')
            ->leftJoin('documents', 'userNotifications.documentId', '=', 'documents.id');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'message') {
            $query = $query->orderBy('userNotifications.message', $direction);
        }

        if ($orderBy == 'createdDate') {
            $query = $query->orderBy('userNotifications.message', $direction);
        }

        if ($attributes->name) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('userNotifications.message', 'like', '%' . $attributes->name . '%')
                    ->orWhere(function ($query) use ($attributes) {
                        $query->where('documents.name', 'like', '%' . $attributes->name . '%');
                    });
            });
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getUserNotificaionCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            throw new RepositoryException('User does not exist.');
        }
        $query = UserNotifications::query()
            ->where('userNotifications.userId', '=', $userId)
            ->where('documents.isDeleted', '=', false)
            ->leftJoin('documents', 'userNotifications.documentId', '=', 'documents.id');

        if ($attributes->name) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('userNotifications.message', 'like', '%' . $attributes->name . '%')
                    ->orWhere(function ($query) use ($attributes) {
                        $query->where('documents.name', 'like', '%' . $attributes->name . '%');
                    });
            });
        }

        $count = $query->count();
        return $count;
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
        return $result;
    }

    public function markAllAsRead()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        if ($userId == null) {
            throw new RepositoryException('User does not exist.');
        }

        $userNotifications = UserNotifications::where('userId', $userId)->get();

        foreach ($userNotifications as $userNotification) {
            $userNotification->isRead = true;
            $userNotification->save();
        }

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
        return;
    }
}
