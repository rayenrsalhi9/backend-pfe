<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface UserNotificationRepositoryInterface extends BaseRepositoryInterface
{
    public function getUserNotificaions($attributes);
    public function getUserNotificaionCount($attributes);
    public function markAsRead($request);
    public function markAllAsRead();
    public function markAsReadByDocumentId($documentId);
    public function getTop10Notification();
 
}
