<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface ReminderRepositoryInterface extends BaseRepositoryInterface
{
    public function getRemindersCount($attributes);
    public function addReminders($attributes);
    public function getReminders($attributes);
    public function updateReminders($attributes, $id);
    public function findReminder($id);
    public function  getReminderForLoginUser($attributes);
    public function getReminderForLoginUserCount($attributes);
    public function deleteReminderCurrentUser($id);
}
