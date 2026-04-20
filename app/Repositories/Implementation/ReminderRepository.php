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

    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return Reminders::class;
    }
    public function getReminders($attributes)
    {
        $query = Reminders::select([
            'reminders.createdDate', 'reminders.startDate', 'reminders.endDate', 'reminders.id', 'reminders.subject', 'reminders.message',
            'reminders.frequency', 'reminders.category'
        ]);

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'subject') {
            $query = $query->orderBy('subject', $direction);
        } else if ($orderBy == 'message') {
            $query = $query->orderBy('message', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        }

        if ($attributes->subject) {
            $query = $query->where('subject',  'like', '%' . $attributes->subject . '%');
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }

        if ($attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }
        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();
        return $results;
    }

    public function getRemindersCount($attributes)
    {
        $query = Reminders::query();

        if ($attributes->subject) {
            $query = $query->where('subject', $attributes->subject);
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }
        if ($attributes->frequency != '') {
            $query = $query->where('frequency',  $attributes->frequency);
        }

        $count = $query->count();
        return $count;
    }

    public function addReminders($request)
    {
        try {
            DB::beginTransaction();

            $requestData = is_array($request) ? $request : $request->all();

            if ($requestData['frequency'] == '') {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            if (!$requestData['isRepeated']) {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            $model = $this->model->newInstance($requestData);
            $saved = $model->save();

            $currentUserId = Auth::parseToken()->getPayload()->get('userId');

            $notificationPayloads = [];
            $uniqueUserIds = [];
            if ($requestData['reminderUsers']) {
                $model->reminderUsers()->createMany($requestData['reminderUsers']);

                foreach ($requestData['reminderUsers'] as $user) {
                    if (isset($uniqueUserIds[$user['userId']]) || $user['userId'] === $currentUserId) {
                        continue;
                    }
                    $uniqueUserIds[$user['userId']] = true;

                    $notificationPayloads[] = [
                        'userId' => $user['userId'],
                        'message' => 'New reminder: ' . $requestData['subject'],
                    ];
                }

                if (!isset($uniqueUserIds[$currentUserId])) {
                    $notificationPayloads[] = [
                        'userId' => $currentUserId,
                        'message' => 'You created a reminder: ' . $requestData['subject'],
                    ];
                }
            } else {
                $model->reminderUsers()->createMany(array(['userId' => $currentUserId, 'reminderId' => $model->id]));

                $notificationPayloads[] = [
                    'userId' => $currentUserId,
                    'message' => 'You created a reminder: ' . $requestData['subject'],
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
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function updateReminders($request, $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->findOrFail($id);

            $requestData = is_array($request) ? $request : $request->all();

            if (array_key_exists('subject', $requestData)) {
                $model->subject = $requestData['subject'];
            }
            if (array_key_exists('message', $requestData)) {
                $model->message = $requestData['message'];
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

            $saved = $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            $existingReminderUsers = ReminderUsers::where('reminderId', '=', $id)->pluck('userId')->toArray();

            ReminderUsers::where('reminderId', '=', $id)->delete();

            DailyReminders::where('reminderId', '=', $id)->delete();

            HalfYearlyReminders::where('reminderId', '=', $id)->delete();

            QuarterlyReminders::where('reminderId', '=', $id)->delete();

            $notificationPayloads = [];

            if ($reminderUsers) {
                $model->reminderUsers()->createMany($reminderUsers);

                $currentUserId = Auth::parseToken()->getPayload()->get('userId');
                $newUserIds = array_filter(array_column($reminderUsers, 'userId'), function($userId) use ($existingReminderUsers, $currentUserId) {
                    return !in_array($userId, $existingReminderUsers) && $userId !== $currentUserId;
                });

                foreach ($newUserIds as $userId) {
                    $notificationPayloads[] = [
                        'userId' => $userId,
                        'message' => 'Reminder updated: ' . $requestData['subject'],
                    ];
                }
            } else {
                $userId = Auth::parseToken()->getPayload()->get('userId');
                $model->reminderUsers()->createMany(array(['userId' => $userId, 'reminderId' => $model->id]));

                $notificationPayloads[] = [
                    'userId' => $userId,
                    'message' => 'Reminder updated: ' . $requestData['subject'],
                ];
            }

            if (!empty($dailyReminders)) {
                $model->dailyReminders()->createMany($dailyReminders);
            }

            if (!empty($halfYearlyReminders)) {
                $model->halfYearlyReminders()->createMany($halfYearlyReminders);
            }

            if (!empty($quarterlyReminders)) {
                $model->quarterlyReminders()->createMany($quarterlyReminders);
            }

            if (!$saved) {
                throw new RepositoryException('Error in saving data.');
            }
            DB::commit();

            if (!empty($notificationPayloads)) {
                foreach ($notificationPayloads as $payload) {
                    UserNotifications::create([
                        'userId' => $payload['userId'],
                        'isRead' => 0,
                        'message' => $payload['message'],
                        'type' => 'reminder'
                    ]);

                    $this->triggerPusherNotification($payload['userId'], $payload['message'], $model->id);
                }
            }

            return $result;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Error updating reminder: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reminder: ' . $e->getMessage());
            throw new RepositoryException('Error in saving data: ' . $e->getMessage());
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

    public function  getReminderForLoginUser($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::select([
            'reminders.createdDate', 'reminders.startDate', 'reminders.endDate', 'reminders.id', 'reminders.subject', 'reminders.message',
            'reminders.frequency', 'reminders.category'
        ])
            ->orWhereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            });

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'subject') {
            $query = $query->orderBy('subject', $direction);
        } else if ($orderBy == 'message') {
            $query = $query->orderBy('message', $direction);
        } else if ($orderBy == 'startDate') {
            $query = $query->orderBy('startDate', $direction);
        } else if ($orderBy == 'endDate') {
            $query = $query->orderBy('endDate', $direction);
        }

        if ($attributes->subject) {
            $query = $query->where('subject',  'like', '%' . $attributes->subject . '%');
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }

        if ($attributes->frequency != '') {
            $query = $query->where('frequency', $attributes->frequency);
        }
        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();
        return $results;
    }

    public function getReminderForLoginUserCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $query = Reminders::query()
            ->orWhereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            });

        if ($attributes->subject) {
            $query = $query->where('subject', $attributes->subject);
        }

        if ($attributes->message) {
            $query = $query->where('message', 'like', '%' . $attributes->message . '%');
        }
        if ($attributes->frequency != '') {
            $query = $query->where('frequency',  $attributes->frequency);
        }

        $count = $query->count();
        return $count;
    }

    public function deleteReminderCurrentUser($id)
    {

        $userId = Auth::parseToken()->getPayload()->get('userId');
        return  ReminderUsers::where('reminderId', '=', $id)->Where('userId', '=', $userId)->delete();
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

            Log::info('Pusher notification triggered', [
                'action' => 'pusher_triggered',
                'notification_type' => 'reminder',
                'reminder_id' => $reminderId
            ]);

            $pusher->trigger("user.{$userId}", 'notification', [
                'type' => 'reminder',
                'data' => [
                    'message' => $message
                ]
            ]);

            Log::info('Pusher notification triggered successfully');
        } catch (\Exception $e) {
            Log::error('Pusher notification failed: ' . $e->getMessage());
        }
    }
}
