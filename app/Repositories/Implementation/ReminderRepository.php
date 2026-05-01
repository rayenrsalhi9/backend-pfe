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
use App\Traits\CacheableTrait;

class ReminderRepository extends BaseRepository implements ReminderRepositoryInterface
{
    use CacheableTrait;

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
        $userId = Auth::id();

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

        $userId = Auth::id();

        $model->isDeleted = true;
        $model->deletedBy = $userId;
        $model->deleted_at = Carbon::now();

        $saved = $model->save();
        $this->flushCacheTag('calendar');
        return $saved;
    }

    public function addReminders($request)
    {
        try {
            DB::beginTransaction();

            $requestData = is_array($request) ? $request : $request->all();

            // Simple Validation
            if (empty($requestData['eventName'])) {
                throw new RepositoryException('Event name is required', 422);
            }
            if (empty($requestData['startDate'])) {
                throw new RepositoryException('Start date is required', 422);
            }

            if (!isset($requestData['frequency']) || $requestData['frequency'] == '') {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            if (!isset($requestData['isRepeated']) || !$requestData['isRepeated']) {
                $requestData['frequency'] = FrequencyEnum::OneTime->value;
            }

            $model = $this->model->newInstance($requestData);
            $saved = $model->save();

            $currentUserId = Auth::id();

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

            $this->flushCacheTag('calendar');
            return $saved;
        } catch (RepositoryException $e) {
            DB::rollBack();
            throw $e;
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
            if (array_key_exists('eventName', $requestData) && empty($requestData['eventName'])) {
                throw new RepositoryException('Event name cannot be empty', 422);
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
            $currentUserId = Auth::id();

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
                if (!in_array($currentUserId, $existingReminderUsers)) {
                    $model->reminderUsers()->createMany([['userId' => $currentUserId, 'reminderId' => $model->id]]);

                    $notificationPayloads[] = [
                        'userId' => $currentUserId,
                        'message' => 'Reminder updated: ' . $model->eventName,
                    ];
                }
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

            $this->flushCacheTag('calendar');
            return $this->parseResult($model);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        } catch (RepositoryException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw new RepositoryException('Error saving reminder: ' . $e->getMessage());
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
        if (! $userId = Auth::id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthenticated user');
        }
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
        if (! $userId = Auth::id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthenticated user');
        }
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
        if (!Auth::check()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthenticated user');
        }
        $userId = Auth::id();
        return ReminderUsers::where('reminderId', '=', $id)->where('userId', '=', $userId)->delete();
    }

    public function getCalendarEvents($month, $year)
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthenticated user');
        }

        $cacheKey = "calendar:events:{$userId}:{$month}:{$year}";

        return $this->cacheRemember($cacheKey, 'calendar', $this->getCacheTtl('calendar'), function () use ($month, $year, $userId) {
            $monthStartDate = new \DateTime();
            $monthStartDate->setDate($year, $month, 1);
            $monthEndDate = (clone $monthStartDate)->modify('+1 month')->modify('-1 day');

            $monthStartStr = $monthStartDate->format('Y-m-d');
            $monthEndStr = $monthEndDate->format('Y-m-d');

            $events = [];

            $reminders = Reminders::select(['reminders.*'])
                ->where('reminders.isDeleted', '=', 0)
                ->with(['reminderUsers', 'dailyReminders', 'quarterlyReminders', 'halfYearlyReminders'])
                ->where(function ($q) use ($userId) {
                    $q->where('createdBy', $userId)
                        ->orWhereExists(function ($query) use ($userId) {
                            $query->select(DB::raw(1))
                                ->from('reminderUsers')
                                ->whereRaw('reminderUsers.reminderId = reminders.id')
                                ->where('reminderUsers.userId', '=', $userId);
                        });
                })
                ->where(function ($q) use ($monthStartStr, $monthEndStr) {
                    $q->where(function ($sub) use ($monthStartStr, $monthEndStr) {
                        $sub->where('reminders.frequency', FrequencyEnum::OneTime->value)
                            ->whereBetween('reminders.startDate', [$monthStartStr, $monthEndStr . ' 23:59:59']);
                    })
                    ->orWhere(function ($sub) use ($monthStartStr, $monthEndStr) {
                        $sub->where('reminders.isRepeated', '=', 1)
                            ->where('reminders.startDate', '<=', $monthEndStr . ' 23:59:59')
                            ->where(function ($inner) use ($monthStartStr, $monthEndStr) {
                                $inner->where(function ($daily) use ($monthStartStr) {
                                    $daily->where('reminders.frequency', FrequencyEnum::Daily->value)
                                        ->where(function ($q) use ($monthStartStr) {
                                            $q->where('reminders.endDate', '>=', $monthStartStr)
                                                ->orWhereNull('reminders.endDate');
                                        });
                                })
                                ->orWhere(function ($weekly) use ($monthStartStr) {
                                    $weekly->where('reminders.frequency', FrequencyEnum::Weekly->value)
                                        ->where(function ($q) use ($monthStartStr) {
                                            $q->where('reminders.endDate', '>=', $monthStartStr)
                                                ->orWhereNull('reminders.endDate');
                                        });
                                })
                                ->orWhere(function ($monthly) use ($monthStartStr) {
                                    $monthly->where('reminders.frequency', FrequencyEnum::Monthly->value)
                                        ->where(function ($q) use ($monthStartStr) {
                                            $q->where('reminders.endDate', '>=', $monthStartStr)
                                                ->orWhereNull('reminders.endDate');
                                        });
                                })
                                ->orWhere(function ($quarterly) use ($monthStartStr, $monthEndStr) {
                                    $quarterly->where('reminders.frequency', FrequencyEnum::Quarterly->value)
                                        ->where(function ($q) use ($monthStartStr, $monthEndStr) {
                                            $q->whereHas('quarterlyReminders', function ($qRel) use ($monthStartStr, $monthEndStr) {
                                                $qRel->whereRaw('MONTH(STR_TO_DATE(CONCAT(?, "-", month, "-", day), "%Y-%m-%d")) BETWEEN MONTH(?) AND MONTH(?)', [$monthStartStr, $monthStartStr, $monthEndStr]);
                                            })->orWhereNull('reminders.endDate');
                                        });
                                })
                                ->orWhere(function ($halfYearly) use ($monthStartStr, $monthEndStr) {
                                    $halfYearly->where('reminders.frequency', FrequencyEnum::HalfYearly->value)
                                        ->where(function ($q) use ($monthStartStr, $monthEndStr) {
                                            $q->whereHas('halfYearlyReminders', function ($qRel) use ($monthStartStr, $monthEndStr) {
                                                $qRel->whereRaw('MONTH(STR_TO_DATE(CONCAT(?, "-", month, "-", day), "%Y-%m-%d")) BETWEEN MONTH(?) AND MONTH(?)', [$monthStartStr, $monthStartStr, $monthEndStr]);
                                            })->orWhereNull('reminders.endDate');
                                        });
                                })
                                ->orWhere(function ($yearly) use ($monthStartStr) {
                                    $yearly->where('reminders.frequency', FrequencyEnum::Yearly->value)
                                        ->where(function ($q) use ($monthStartStr) {
                                            $q->where('reminders.endDate', '>=', $monthStartStr)
                                                ->orWhereNull('reminders.endDate');
                                        });
                                });
                            });
                    });
                })
                ->get();

            foreach ($reminders as $r) {
                $reminderStart = new \DateTime($r->startDate);
                $reminderStartTime = $reminderStart->format('H:i:s');
                $reminderEnd = $r->endDate ? new \DateTime($r->endDate) : null;
                $reminderEndTime = $reminderEnd ? $reminderEnd->format('H:i:s') : $reminderStartTime;

                if ($r->frequency === FrequencyEnum::OneTime) {
                    if ($r->startDate >= $monthStartStr && $r->startDate <= $monthEndStr . ' 23:59:59') {
                        $events[] = [
                            'id' => $r->id,
                            'title' => $r->eventName,
                            'description' => $r->description,
                            'start' => $r->startDate,
                            'end' => $r->endDate ?? $r->startDate,
                            'allDay' => false,
                            'category' => $r->category ?? 'normal',
                            'frequency' => FrequencyEnum::OneTime->value,
                        ];
                    }
                }

                $reminderStartDateStr = $r->startDate;

                if ($r->isRepeated == 1 && $reminderStartDateStr <= $monthEndStr . ' 23:59:59') {
                    $this->expandRecurringReminder($r, $userId, $monthStartStr, $monthEndStr, $reminderStartTime, $reminderEndTime, $reminderStartDateStr, $events);
                }
            }

            return $events;
        });
    }

    private function expandRecurringReminder($reminder, $userId, $monthStartStr, $monthEndStr, $startTime, $endTime, $reminderStartDateStr, &$events)
    {
        $monthStart = new \DateTime($monthStartStr);
        $monthEnd = new \DateTime($monthEndStr);
        $reminderStartDate = new \DateTime($reminderStartDateStr);

        $reminderStartDateOnly = date('Y-m-d', strtotime($reminderStartDateStr));

        $year = (int)$monthStart->format('Y');
        $month = (int)$monthStart->format('m');

        if ($reminder->frequency === FrequencyEnum::Daily) {
            $currentDate = clone $monthStart;
            $reminderDateOnly = clone $reminderStartDate;
            $reminderDateOnly->setTime(0, 0, 0);
            if ($currentDate < $reminderDateOnly) {
                $currentDate = clone $reminderDateOnly;
            }
            $monthEndOnly = clone $monthEnd;
            $monthEndOnly->setTime(23, 59, 59);
            while ($currentDate <= $monthEndOnly) {
                $events[] = [
                    'id' => $reminder->id,
                    'title' => $reminder->eventName,
                    'description' => $reminder->description,
                    'start' => $currentDate->format('Y-m-d') . ' ' . $startTime,
                    'end' => $currentDate->format('Y-m-d') . ' ' . $endTime,
                    'allDay' => false,
                    'category' => $reminder->category ?? 'normal',
                    'frequency' => $reminder->frequency->value,
                ];
                $currentDate->modify('+1 day');
            }
        }

        if ($reminder->frequency === FrequencyEnum::Weekly) {
            $targetDayOfWeek = $reminder->dayOfWeek;
            $currentDate = clone $monthStart;
            $monthEndOnly = clone $monthEnd;
            $monthEndOnly->setTime(23, 59, 59);
            while ($currentDate <= $monthEndOnly) {
                $currentDateOnly = $currentDate->format('Y-m-d');
                if ($currentDateOnly >= $reminderStartDateOnly && $currentDate->format('w') == $targetDayOfWeek) {
                    $events[] = [
                        'id' => $reminder->id,
                        'title' => $reminder->eventName,
                        'description' => $reminder->description,
                        'start' => $currentDate->format('Y-m-d') . ' ' . $startTime,
                        'end' => $currentDate->format('Y-m-d') . ' ' . $endTime,
                        'allDay' => false,
                        'category' => $reminder->category ?? 'normal',
                        'frequency' => $reminder->frequency->value,
                    ];
                }
                $currentDate->modify('+1 day');
            }
        }

        if ($reminder->frequency === FrequencyEnum::Monthly) {
            $targetDay = (int)$reminderStartDate->format('d');
            $daysInMonth = (int)$monthEnd->format('t');
            $actualDay = min($targetDay, $daysInMonth);

            $occurrenceDate = new \DateTime($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($actualDay, 2, '0', STR_PAD_LEFT));
            $occurrenceDateOnly = $occurrenceDate->format('Y-m-d');
            if ($occurrenceDateOnly >= $reminderStartDateOnly) {
                $events[] = [
                    'id' => $reminder->id,
                    'title' => $reminder->eventName,
                    'description' => $reminder->description,
                    'start' => $occurrenceDateOnly . ' ' . $startTime,
                    'end' => $occurrenceDateOnly . ' ' . $endTime,
                    'allDay' => false,
                    'category' => $reminder->category ?? 'normal',
                    'frequency' => $reminder->frequency->value,
                ];
            }
        }

        if ($reminder->frequency === FrequencyEnum::Quarterly) {
            $quarterlyConfigs = $reminder->quarterlyReminders;
            if ($quarterlyConfigs && $quarterlyConfigs->count() > 0) {
                foreach ($quarterlyConfigs as $config) {
                    $configMonth = $config->month;
                    $configDay = $config->day;
                    $daysInConfigMonth = (int)date('t', mktime(0, 0, 0, $configMonth, 1, $year));
                    $actualDay = min($configDay, $daysInConfigMonth);

                    if ((int)$configMonth == $month) {
                        $occurrenceDateOnly = $year . '-' . str_pad($configMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($actualDay, 2, '0', STR_PAD_LEFT);
                        if ($occurrenceDateOnly >= $reminderStartDateOnly) {
                            $events[] = [
                                'id' => $reminder->id,
                                'title' => $reminder->eventName,
                                'description' => $reminder->description,
                                'start' => $occurrenceDateOnly . ' ' . $startTime,
                                'end' => $occurrenceDateOnly . ' ' . $endTime,
                                'allDay' => false,
                                'category' => $reminder->category ?? 'normal',
                                'frequency' => $reminder->frequency->value,
                            ];
                        }
                    }
                }
            }
        }

        if ($reminder->frequency === FrequencyEnum::HalfYearly) {
            $halfYearlyConfigs = $reminder->halfYearlyReminders;
            if ($halfYearlyConfigs && $halfYearlyConfigs->count() > 0) {
                foreach ($halfYearlyConfigs as $config) {
                    $configMonth = $config->month;
                    $configDay = $config->day;
                    $daysInConfigMonth = (int)date('t', mktime(0, 0, 0, $configMonth, 1, $year));
                    $actualDay = min($configDay, $daysInConfigMonth);

                    if ((int)$configMonth == $month) {
                        $occurrenceDateOnly = $year . '-' . str_pad($configMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($actualDay, 2, '0', STR_PAD_LEFT);
                        if ($occurrenceDateOnly >= $reminderStartDateOnly) {
                            $events[] = [
                                'id' => $reminder->id,
                                'title' => $reminder->eventName,
                                'description' => $reminder->description,
                                'start' => $occurrenceDateOnly . ' ' . $startTime,
                                'end' => $occurrenceDateOnly . ' ' . $endTime,
                                'allDay' => false,
                                'category' => $reminder->category ?? 'normal',
                                'frequency' => $reminder->frequency->value,
                            ];
                        }
                    }
                }
            }
        }

        if ($reminder->frequency === FrequencyEnum::Yearly) {
            $targetMonth = (int)$reminderStartDate->format('m');
            $targetDay = (int)$reminderStartDate->format('d');
            $daysInTargetMonth = (int)date('t', mktime(0, 0, 0, $targetMonth, 1, $year));
            $actualDay = min($targetDay, $daysInTargetMonth);

            if ($targetMonth == $month) {
                $occurrenceDateOnly = $year . '-' . str_pad($targetMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($actualDay, 2, '0', STR_PAD_LEFT);
                if ($occurrenceDateOnly >= $reminderStartDateOnly) {
                    $events[] = [
                        'id' => $reminder->id,
                        'title' => $reminder->eventName,
                        'description' => $reminder->description,
                        'start' => $occurrenceDateOnly . ' ' . $startTime,
                        'end' => $occurrenceDateOnly . ' ' . $endTime,
                        'allDay' => false,
                        'category' => $reminder->category ?? 'normal',
                        'frequency' => $reminder->frequency->value,
                    ];
                }
            }
        }
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
