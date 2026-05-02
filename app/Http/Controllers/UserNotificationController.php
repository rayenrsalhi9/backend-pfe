<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use Illuminate\Http\Request;
use App\Traits\CacheableTrait;

class UserNotificationController extends Controller
{
    use CacheableTrait;
    private $userNotificationRepository;
    protected $queryString;

    public function __construct(UserNotificationRepositoryInterface $userNotificationRepository)
    {
        $this->userNotificationRepository = $userNotificationRepository;
    }
    public function index()
    {
        return response()->json($this->userNotificationRepository->getTop10Notification());
    }

    public function getNotifications(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->userNotificationRepository->getUserNotificaionCount($queryString);
        return response()->json($this->userNotificationRepository->getUserNotificaions($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function markAsRead(Request $request)
    {
        return  response()->json($this->userNotificationRepository->markAsRead($request), 200);
    }

    public function markAllAsRead(Request $request)
    {
        return  response()->json($this->userNotificationRepository->markAllAsRead($request->only(['excludeTypes'])), 200);
    }
}
