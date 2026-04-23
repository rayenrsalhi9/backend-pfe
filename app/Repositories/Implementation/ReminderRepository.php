<?php

namespace App\Repositories\Implementation;

use App\Models\DailyReminders;
use App\Models\FrequencyEnum;
use App\Models\HalfYearlyReminders;
use App\Models\QuarterlyReminders;
use App\Models\Reminders;
use App\Models\ReminderUsers;
use App\Models\UserNotifications;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Pusher\Pusher;

class ReminderRepository extends BaseRepository implements ReminderRepositoryInterface
{
    protected $model;

    public static function model()
    {
        return Reminders::class;
    }

    public function getReminders($attributes)
    {
        $query = Reminders::select([
            'reminders.createdDate',
            'reminders.startDate',
            'reminders.endDate',
            'reminders.id',
            'reminders.eventName',
            'reminders.description',
            'reminders.frequency',
            'reminders.category'
        ]);

        $orderByArray = explode(' ', $attributes->orderBy ?? 'startDate asc');
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'eventName') {
            $query = $query->orderBy('eventName', $direction);
        } else if ($orderBy == 'description') {
            $query = $query->orderBy('description', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        }

if (isset($attributes->eventName) && $attributes->eventName) {
            $query = $query->where('eventName', 'like', '%' . $attributes->eventName . '%');
        }

if (isset($attributes->description) && $attributes->description) {
            $query = $query->where('description', 'like', '%' . $attributes->description . '%');
        }

        if (isset($attributes->frequency) && $attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }
        $results = $query->skip($attributes->skip ?? 0)->take($attributes->pageSize ?? 10)->get();
        return $results;
    }

    public function getRemindersCount($attributes)
    {
        $query = Reminders::query();

if (isset($attributes->eventName) && $attributes->eventName) {
            $query = $query->where('eventName', 'like', '%' . $attributes->eventName . '%');
        }

if (isset($attributes->description) && $attributes->description) {
            $query = $query->where('description', 'like', '%' . $attributes->description . '%');
        }

        if (isset($attributes->frequency) && $attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }

        return $query->count();
    }

    private function checkOwnership(Reminders $model)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');

        // Owner check
        if ($model->createdBy === $userId) {
            return true;
        }

        // Assigned user check
        $isAssigned = $model->reminderUsers()->where('userId', $userId)->exists();
        if ($isAssigned) {
            return true;
        }

        throw new RepositoryException('Access Denied: You do not have permission to modify this reminder.', 403);
    }

    public function delete($id)
    {
        $model = $this->model->findOrFail($id);
        $this->checkOwnership($model);

        $userId = Auth::parseToken()->getPayload()->get('userId');

        $model->isDeleted = true;
        $model->deletedBy = $userId;
        $model->deleted_at = Carbon::now();

        return $model->save();
    }

    public function addReminders($request)
    {
        try {
            DB::beginTransaction();

            $requestData = is_array($request) ? $request : $request->all();

            // Simple Validation
            if (empty($requestData['eventName'])) {
                throw new \Exception('Event name is required');
            }
            if (empty($requestData['startDate'])) {
                throw new \Exception('Start date is required');
            }

            if (!isset($requestData['frequency']) || $requestData['frequency'] == '') {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            if (!isset($requestData['isRepeated']) || !$requestData['isRepeated']) {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            $model = $this->model->newInstance($requestData);
            $saved = $model->save();

            $currentUserId = Auth::parseToken()->getPayload()->get('userId');

            $notificationPayloads = [];
            $uniqueUserIds = [];
            if (!empty($requestData['reminderUsers'])) {
                $model->reminderUsers()->createMany($requestData['reminderUsers']);

                foreach ($requestData['reminderUsers'] as $user) {
                    if (isset($uniqueUserIds[$user['userId']]) || $user['userId'] === $currentUserId) {
                        continue;
                    }
                    $uniqueUserIds[$user['userId']] = true;

                    $notificationPayloads[] = [
                        'userId' => $user['userId'],
                        'message' => 'New reminder: ' . $requestData['eventName'],
                    ];
                }

                if (!isset($uniqueUserIds[$currentUserId])) {
                    $notificationPayloads[] = [
                        'userId' => $currentUserId,
                        'message' => 'You created a reminder: ' . $requestData['eventName'],
                    ];
                }
            } else {
                $model->reminderUsers()->createMany([['userId' => $currentUserId, 'reminderId' => $model->id]]);

                $notificationPayloads[] = [
                    'userId' => $currentUserId,
                    'message' => 'You created a reminder: ' . $requestData['eventName'],
                ];
            }

            if (!empty($requestData['dailyReminders'])) {
                $model->dailyReminders()->createMany($requestData['dailyReminders']);
            }

            if (!empty($requestData['halfYearlyReminders'])) {
                $model->halfYearlyReminders()->createMany($requestData['halfYearlyReminders']);
            }

            if (!empty($requestData['quarterlyReminders'])) {
                $model->quarterlyReminders()->createMany($requestData['quarterlyReminders']);
            }

            DB::commit();

            foreach ($notificationPayloads as $payload) {
                UserNotifications::create([
                    'userId' => $payload['userId'],
                    'isRead' => 0,
                    'message' => $payload['message'],
                    'type' => 'reminder'
                ]);

                $this->triggerPusherNotification($payload['userId'], $payload['message'], $model->id);
            }

            return $saved;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw new RepositoryException('Error saving reminder: ' . $e->getMessage());
        }
    }

    public function updateReminders($request, $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->findOrFail($id);
            $this->checkOwnership($model);

            $requestData = is_array($request) ? $request : $request->all();

            // Simple Validation
            if (isset($requestData['eventName']) && empty($requestData['eventName'])) {
                throw new \Exception('Event name cannot be empty');
            }

            if (array_key_exists('eventName', $requestData)) {
                $model->eventName = $requestData['eventName'];
            }
            if (array_key_exists('description', $requestData)) {
                $model->description = $requestData['description'];
            }
            if (array_key_exists('frequency', $requestData)) {
                $model->frequency = $requestData['frequency'];
            }
            if (array_key_exists('dayOfWeek', $requestData)) {
                $model->dayOfWeek = $requestData['dayOfWeek'];
            }
            if (array_key_exists('isRepeated', $requestData)) {
                $model->isRepeated = $requestData['isRepeated'];
            }
            if (array_key_exists('isEmailNotification', $requestData)) {
                $model->isEmailNotification = $requestData['isEmailNotification'];
            }
            if (array_key_exists('color', $requestData) || array_key_exists('category', $requestData)) {
                $model->category = $requestData['color'] ?? $requestData['category'] ?? 'normal';
            }
            if (array_key_exists('startDate', $requestData)) {
                $model->startDate = $requestData['startDate'];
            }
            if (array_key_exists('endDate', $requestData)) {
                $model->endDate = $requestData['endDate'];
            }

            $reminderUsers = array_key_exists('reminderUsers', $requestData) ? $requestData['reminderUsers'] : [];
            $dailyReminders = array_key_exists('dailyReminders', $requestData) ? $requestData['dailyReminders'] : [];
            $halfYearlyReminders = array_key_exists('halfYearlyReminders', $requestData) ? $requestData['halfYearlyReminders'] : [];
            $quarterlyReminders = array_key_exists('quarterlyReminders', $requestData) ? $requestData['quarterlyReminders'] : [];

            $hasReminderUsers = array_key_exists('reminderUsers', $requestData);
            $hasDailyReminders = array_key_exists('dailyReminders', $requestData);
            $hasHalfYearlyReminders = array_key_exists('halfYearlyReminders', $requestData);
            $hasQuarterlyReminders = array_key_exists('quarterlyReminders', $requestData);

            $saved = $model->save();
            $this->resetModel();

            $existingReminderUsers = ReminderUsers::where('reminderId', '=', $id)->pluck('userId')->toArray();

            if ($hasReminderUsers) {
                ReminderUsers::where('reminderId', '=', $id)->delete();
            }
            if ($hasDailyReminders) {
                DailyReminders::where('reminderId', '=', $id)->delete();
            }
            if ($hasHalfYearlyReminders) {
                HalfYearlyReminders::where('reminderId', '=', $id)->delete();
            }
            if ($hasQuarterlyReminders) {
                QuarterlyReminders::where('reminderId', '=', $id)->delete();
            }

            $notificationPayloads = [];
            $currentUserId = Auth::parseToken()->getPayload()->get('userId');

            if ($hasReminderUsers) {
                $model->reminderUsers()->createMany($reminderUsers);

                $newUserIds = array_filter(array_column($reminderUsers, 'userId'), function ($userId) use ($existingReminderUsers, $currentUserId) {
                    return !in_array($userId, $existingReminderUsers) && $userId !== $currentUserId;
                });

                foreach ($newUserIds as $userId) {
                    $notificationPayloads[] = [
                        'userId' => $userId,
                        'message' => 'Reminder updated: ' . $model->eventName,
                    ];
                }
            }

            if (!$hasReminderUsers) {
                $model->reminderUsers()->createMany([['userId' => $currentUserId, 'reminderId' => $model->id]]);

                $notificationPayloads[] = [
                    'userId' => $currentUserId,
                    'message' => 'Reminder updated: ' . $model->eventName,
                ];
            }

            if ($hasDailyReminders) {
                $model->dailyReminders()->createMany($dailyReminders);
            }
            if ($hasHalfYearlyReminders) {
                $model->halfYearlyReminders()->createMany($halfYearlyReminders);
            }
            if ($hasQuarterlyReminders) {
                $model->quarterlyReminders()->createMany($quarterlyReminders);
            }

            if (!$saved) {
                throw new RepositoryException('Error in saving data.');
            }
            DB::commit();

            foreach ($notificationPayloads as $payload) {
                UserNotifications::create([
                    'userId' => $payload['userId'],
                    'isRead' => 0,
                    'message' => $payload['message'],
                    'type' => 'reminder'
                ]);

                $this->triggerPusherNotification($payload['userId'], $payload['message'], $model->id);
            }

            return $this->parseResult($model);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw new RepositoryException('Error saving reminder');
        }
    }

    public function findReminder($id)
    {
        $model = $this->model->with('reminderUsers')
            ->with('quarterlyReminders')
            ->with('halfYearlyReminders')
            ->with('dailyReminders')->findOrFail($id);
        $this->resetModel();
        return $this->parseResult($model);
    }

    public function getReminderForLoginUser($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::select([
            'reminders.createdDate',
            'reminders.startDate',
            'reminders.endDate',
            'reminders.id',
            'reminders.eventName',
            'reminders.description',
            'reminders.frequency',
            'reminders.category'
        ])
            ->where(function ($q) use ($userId) {
                $q->where('createdBy', $userId)
                    ->orWhereExists(function ($query) use ($userId) {
                        $query->select(DB::raw(1))
                            ->from('reminderUsers')
                            ->whereRaw('reminderUsers.reminderId = reminders.id')
                            ->where('reminderUsers.userId', '=', $userId);
                    });
            });

        $orderByArray = explode(' ', $attributes->orderBy ?? 'startDate asc');
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'eventName') {
            $query = $query->orderBy('eventName', $direction);
        } else if ($orderBy == 'description') {
            $query = $query->orderBy('description', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        }

if (isset($attributes->eventName) && $attributes->eventName) {
            $query = $query->where('eventName', 'like', '%' . $attributes->eventName . '%');
        }

if (isset($attributes->description) && $attributes->description) {
            $query = $query->where('description', 'like', '%' . $attributes->description . '%');
        }

        if (isset($attributes->frequency) && $attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }

        $results = $query->skip($attributes->skip ?? 0)->take($attributes->pageSize ?? 10)->get();
        return $results;
    }

    public function getReminderForLoginUserCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::query()
            ->where(function ($q) use ($userId) {
                $q->where('createdBy', $userId)
                    ->orWhereExists(function ($query) use ($userId) {
                        $query->select(DB::raw(1))
                            ->from('reminderUsers')
                            ->whereRaw('reminderUsers.reminderId = reminders.id')
                            ->where('reminderUsers.userId', '=', $userId);
                    });
            });

if (isset($attributes->eventName) && $attributes->eventName) {
            $query = $query->where('eventName', 'like', '%' . $attributes->eventName . '%');
        }

if (isset($attributes->description) && $attributes->description) {
            $query = $query->where('description', 'like', '%' . $attributes->description . '%');
        }

        if (isset($attributes->frequency) && $attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }

        return $query->count();
    }

    public function deleteReminderCurrentUser($id)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        return ReminderUsers::where('reminderId', '=', $id)->where('userId', '=', $userId)->delete();
    }

    private function triggerPusherNotification(string $userId, string $message, ?string $reminderId = null): void
    {
        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => config('broadcasting.connections.pusher.options.useTLS', true)
                ]
            );

            $pusher->trigger("private-App.Models.User.{$userId}", 'notification', [
                'type' => 'reminder',
                'data' => [
                    'message' => $message,
                    'reminder_id' => $reminderId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Pusher notification failed: ' . $e->getMessage());
        }
    }
}
