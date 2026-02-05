<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\ReminderRepositoryInterface;

class ReminderController extends Controller
{
    private $reminderRepository;
    protected $queryString;

    public function __construct(ReminderRepositoryInterface $reminderRepository)
    {
        $this->reminderRepository = $reminderRepository;
    }

    public function getReminders(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->reminderRepository->getRemindersCount($queryString);
        return response()->json($this->reminderRepository->getReminders($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function getReminderForLoginUser(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->reminderRepository->getReminderForLoginUserCount($queryString);
        return response()->json($this->reminderRepository->getReminderForLoginUser($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function addReminder(Request $request)
    {
        return  response($this->reminderRepository->addReminders($request->all()), 201);
    }

    public function updateReminder(Request $request, $id)
    {
        return  response($this->reminderRepository->updateReminders($request, $id), 201);
    }

    public function edit($id)
    {
        return response()->json($this->reminderRepository->findReminder($id));
    }

    public function deleteReminder($id)
    {
        return response($this->reminderRepository->delete($id), 204);
    }

    public function deleteReminderCurrentUser($id)
    {
        return response($this->reminderRepository->deleteReminderCurrentUser($id));
    }
}
