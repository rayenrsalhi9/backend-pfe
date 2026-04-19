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
            'reminders.frequency', 'reminders.documentId', 'reminders.category', 'documents.name as documentName'
        ])->leftjoin('documents', 'reminders.documentId', '=', 'documents.id');

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
        } else if ($orderBy == 'documentName') {
            $query = $query->orderBy('documents.name', $direction);
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
        $query = Reminders::query()
            ->leftjoin('documents', 'reminders.documentId', '=', 'documents.id');


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

            if ($request['frequency'] == '') {
                $request['frequency'] = FrequencyEnum::OneTime->value;
            }

            if (!$request['isRepeated']) {
                $request['frequency'] = FrequencyEnum::OneTime->value;
            }

            $model = $this->model->newInstance($request);
            $saved = $model->save();
            
            $currentUserId = Auth::parseToken()->getPayload()->get('userId');

            $uniqueUserIds = [];
            if ($request['reminderUsers']) {
                $model->reminderUsers()->createMany($request['reminderUsers']);
                
                foreach ($request['reminderUsers'] as $user) {
                    if (isset($uniqueUserIds[$user['userId']]) || $user['userId'] === $currentUserId) {
                        continue;
                    }
                    $uniqueUserIds[$user['userId']] = true;
                    
                    UserNotifications::create([
                        'userId' => $user['userId'],
                        'isRead' => 0,
                        'message' => 'New reminder: ' . $request['subject'],
                        'documentId' => $request['documentId'] ?? null,
                        'type' => 'reminder'
                    ]);
                    
                    $this->triggerPusherNotification($user['userId'], 'New reminder: ' . $request['subject']);
                }
                
                if (!isset($uniqueUserIds[$currentUserId])) {
                    UserNotifications::create([
                        'userId' => $currentUserId,
                        'isRead' => 0,
                        'message' => 'You created a reminder: ' . $request['subject'],
                        'documentId' => $request['documentId'] ?? null,
                        'type' => 'reminder'
                    ]);
                    
                    $this->triggerPusherNotification($currentUserId, 'You created a reminder: ' . $request['subject']);
                }
            } else {
                $model->reminderUsers()->createMany(array(['userId' => $currentUserId, 'reminderId' => $model->id]));
                
                UserNotifications::create([
                    'userId' => $currentUserId,
                    'isRead' => 0,
                    'message' => 'You created a reminder: ' . $request['subject'],
                    'documentId' => $request['documentId'] ?? null,
                    'type' => 'reminder'
                ]);
                
                $this->triggerPusherNotification($currentUserId, 'You created a reminder: ' . $request['subject']);
            }

            if (isset($request['dailyReminders'])) {
                $model->dailyReminders()->createMany($request['dailyReminders']);
            }

            if (isset($request['halfYearlyReminders'])) {
                $model->halfYearlyReminders()->createMany($request['halfYearlyReminders']);
            }

            if (isset($request['quarterlyReminders'])) {
                $model->quarterlyReminders()->createMany($request['quarterlyReminders']);
            }

            DB::commit();
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

            $model->subject = $request['subject'];
            $model->documentId = $request['documentId'];
            $model->message = $request['message'];
            $model->frequency = $request['frequency'];
            $model->dayOfWeek = $request['dayOfWeek'];
            $model->isRepeated = $request['isRepeated'];
            $model->isEmailNotification = $request['isEmailNotification'];
            $model->category = $request['color'] ?? $request['category'] ?? 'normal';
            $model->startDate = $request['startDate'];
            $model->endDate = $request['endDate'];
            $reminderUsers = $request['reminderUsers'] ?? [];
            $dailyReminders = $request['dailyReminders'] ?? [];
            $halfYearlyReminders = $request['halfYearlyReminders'] ?? [];
            $quarterlyReminders = $request['quarterlyReminders'] ?? [];

            $saved = $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            $existingReminderUsers = ReminderUsers::where('reminderId', '=', $id)->pluck('userId')->toArray();

            $reminderUser = ReminderUsers::where('reminderId', '=', $id)->delete();

            $dailyReminder = DailyReminders::where('reminderId', '=', $id)->delete();

            $halfYearlyReminder = HalfYearlyReminders::where('reminderId', '=', $id)->delete();

            $quarterlyReminder = QuarterlyReminders::where('reminderId', '=', $id)->delete();

            if ($reminderUsers) {
                $model->reminderUsers()->createMany($reminderUsers);

                $currentUserId = Auth::parseToken()->getPayload()->get('userId');
                $newUserIds = array_filter(array_column($reminderUsers, 'userId'), function($userId) use ($existingReminderUsers, $currentUserId) {
                    return !in_array($userId, $existingReminderUsers) && $userId !== $currentUserId;
                });

                foreach ($newUserIds as $userId) {
                    UserNotifications::create([
                        'userId' => $userId,
                        'isRead' => 0,
                        'message' => 'Reminder updated: ' . $request['subject'],
                        'documentId' => $request['documentId'] ?? null,
                        'type' => 'reminder'
                    ]);

                    $this->triggerPusherNotification($userId, 'Reminder updated: ' . $request['subject']);
                }
            } else {
                $userId = Auth::parseToken()->getPayload()->get('userId');
                $model->reminderUsers()->createMany(array(['userId' => $userId, 'reminderId' => $model->id]));
            }

            if (isset($dailyReminders)) {
                $model->dailyReminders()->createMany($dailyReminders);
            }

            if (isset($halfYearlyReminders)) {
                $model->halfYearlyReminders()->createMany($halfYearlyReminders);
            }

            if (isset($quarterlyReminders)) {
                $model->quarterlyReminders()->createMany($quarterlyReminders);
            }

            if (!$saved) {
                throw new RepositoryException('Error in saving data.');
            }
            DB::commit();
            return $result;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Error updating reminder: ' . $e->getMessage());
            return response()->json(['message' => 'Reminder not found.'], 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reminder: ' . $e->getMessage());
            return response()->json(['message' => 'Error in saving data.'], 409);
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
            'reminders.frequency', 'reminders.documentId', 'reminders.category', 'documents.name as documentName'
        ])
            ->leftJoin('documents', function ($join) {
                $join->on('reminders.documentId', '=', 'documents.id')
                    ->where('documents.isDeleted', '=', false);
            })
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
        } else if ($orderBy == 'documentName') {
            $query = $query->orderBy('documents.name', $direction);
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
            ->leftJoin('documents', function ($join) {
                $join->on('reminders.documentId', '=', 'documents.id')
                    ->where('documents.isDeleted', '=', false);
            })
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
    
    private function triggerPusherNotification(string $userId, string $message): void
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
                'reminder_id' => $model->id ?? null
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
