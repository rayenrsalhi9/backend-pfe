<?php

namespace App\Repositories\Implementation;

use App\Models\ReminderSchedulers;
use App\Repositories\Contracts\NotificationScheduleInterface;
use App\Repositories\Implementation\ConnectionMappingRepository;
use Carbon\Carbon;
use App\Models\FrequencyEnum;
use App\Models\Reminders;
use App\Models\SendEmails;
use App\Models\UserNotifications;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Users;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\EmailRepositoryInterface;


//use Your Model

/**
 * Class NotificationScheduleRepository.
 */
class NotificationScheduleRepository extends BaseRepository implements NotificationScheduleInterface
{
    /**
     * @var Model
     */
    protected $model;


    public $connectionMappingRepository;
    public $emailRepository;

    function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->connectionMappingRepository = new ConnectionMappingRepository();
        $this->emailRepository = $emailRepository;
    }

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return ReminderSchedulers::class;
    }

    public function dailyReminder()
    {
        $currentDate = Carbon::now();
        $dayOfTheWeek = Carbon::now()->dayOfWeek;
        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->join('dailyReminders', 'reminders.id', '=', 'dailyReminders.reminderId')
            ->where('reminders.frequency', '=', FrequencyEnum::Daily->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->where('dailyReminders.dayOfWeek', '=', $dayOfTheWeek)
            ->where('dailyReminders.isActive', '=', 1)
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        $reminders = $reminderQuery->get();
        foreach ($reminders as $r) {
            foreach ($r['reminderUsers'] as $users) {
                $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                $model = ReminderSchedulers::create([
                    'duration' => $duration,
                    'isActive' => 1,
                    'frequency' => $r['frequency'],
                    'userId' => $users['userId'],
                    'isRead' => 0,
                    'isEmailNotification' => $r['isEmailNotification'],
                    'subject' => $r['eventName'],
                    'message' => $r['description'],
                    'createdDate' => Carbon::now(),
                ]);
            }
            $model->save();
        }
    }

    public function weeklyReminder()
    {
        $currentDate = Carbon::now();
        $dayOfTheWeek = Carbon::now()->dayOfWeek;

        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->where('reminders.frequency', '=', FrequencyEnum::Weekly->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->where('reminders.dayOfWeek', '=', $dayOfTheWeek)
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        $reminders = $reminderQuery->get();
        foreach ($reminders as $r) {
            foreach ($r['reminderUsers'] as $users) {
                $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                $model = ReminderSchedulers::create([
                    'duration' => $duration,
                    'isActive' => 1,
                    'frequency' => $r['frequency'],
                    'userId' => $users['userId'],
                    'isRead' => 0,
                    'isEmailNotification' => $r['isEmailNotification'],
                    'subject' => $r['eventName'],
                    'message' => $r['description'],
                    'createdDate' => Carbon::now(),
                ]);
            }
            $model->save();
        }
    }

    public function monthlyReminder()
    {
        $currentDate = Carbon::now();  //Carbon::create(2023, 02, 27);

        $lastDayOfMonth = Carbon::now()->endOfMonth()->format("d");

        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->where('reminders.frequency', '=', FrequencyEnum::Monthly->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        if ($lastDayOfMonth == 28) {
            $reminderQuery = $reminderQuery
                ->whereDay('reminders.startDate', '>=', 28);
        } else if ($lastDayOfMonth == 29) {
            $reminderQuery = $reminderQuery
                ->whereDay('reminders.startDate', '>=', 29);
        } else if ($lastDayOfMonth == 30) {
            $reminderQuery = $reminderQuery
                ->whereDay('reminders.startDate', '>=', 30);
        } else {
            $reminderQuery = $reminderQuery
                ->whereDay('reminders.startDate', '=', $currentDate->format("d"));;
        }

        $reminders = $reminderQuery->get();

        if ($reminders != null && $reminders->count() > 0) {
            foreach ($reminders as $r) {
                foreach ($r['reminderUsers'] as $users) {
                    $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                    $model = ReminderSchedulers::create([
                        'duration' => $duration,
                        'isActive' => 1,
                        'frequency' => $r['frequency'],
                        'userId' => $users['userId'],
                        'isRead' => 0,
                        'isEmailNotification' => $r['isEmailNotification'],
                        'subject' => $r['eventName'],
                        'message' => $r['description'],
                        'createdDate' => Carbon::now(),
                    ]);
                }
                $model->save();
            }
        }
    }

    public function quarterlyReminder()
    {
        $currentDate = Carbon::now();
        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->join('quarterlyReminders', 'reminders.id', '=', 'quarterlyReminders.reminderId')
            ->where('reminders.frequency', '=', FrequencyEnum::Quarterly->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->where('quarterlyReminders.day', '=',  $currentDate->format("d"))
            ->where('quarterlyReminders.month', '=',  $currentDate->format("m"))
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        $reminders = $reminderQuery->get();
        foreach ($reminders as $r) {
            foreach ($r['reminderUsers'] as $users) {
                $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                $model = ReminderSchedulers::create([
                    'duration' => $duration,
                    'isActive' => 1,
                    'frequency' => $r['frequency'],
                    'userId' => $users['userId'],
                    'isRead' => 0,
                    'isEmailNotification' => $r['isEmailNotification'],
                    'subject' => $r['eventName'],
                    'message' => $r['description'],
                    'createdDate' => Carbon::now(),
                ]);
            }
            $model->save();
        }
    }

    public function halfYearlyReminder()
    {
        $currentDate = Carbon::now();
        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->join('halfYearlyReminders', 'reminders.id', '=', 'halfYearlyReminders.reminderId')
            ->where('reminders.frequency', '=', FrequencyEnum::HalfYearly->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->where('halfYearlyReminders.day', '=',  $currentDate->format("d"))
            ->where('halfYearlyReminders.month', '=',  $currentDate->format("m"))
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        $reminders = $reminderQuery->get();
        foreach ($reminders as $r) {
            foreach ($r['reminderUsers'] as $users) {
                $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                $model = ReminderSchedulers::create([
                    'duration' => $duration,
                    'isActive' => 1,
                    'frequency' => $r['frequency'],
                    'userId' => $users['userId'],
                    'isRead' => 0,
                    'isEmailNotification' => $r['isEmailNotification'],
                    'subject' => $r['eventName'],
                    'message' => $r['description'],
                    'createdDate' => Carbon::now(),
                ]);
            }
            $model->save();
        }
    }

    public function yearlyReminder()
    {
        $currentDate = Carbon::now();
        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->where('reminders.frequency', '=', FrequencyEnum::Yearly->value)
            ->whereDate('reminders.startDate', '<=', $currentDate)
            ->whereDay('reminders.startDate', '=', $currentDate->format("d"))
            ->whereMonth('reminders.startDate', '=',  $currentDate->format("m"))
            ->where(function ($query) use ($currentDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($currentDate) {
                        $query->whereDate('reminders.endDate', '>=', $currentDate);
                    });
            });

        $reminders = $reminderQuery->get();
        if ($reminders != null && $reminders->count() > 0) {
            foreach ($reminders as $r) {
                foreach ($r['reminderUsers'] as $users) {
                    $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                    $model = ReminderSchedulers::create([
                        'duration' => $duration,
                        'isActive' => 1,
                        'frequency' => $r['frequency'],
                        'userId' => $users['userId'],
                        'isRead' => 0,
                        'isEmailNotification' => $r['isEmailNotification'],
                        'subject' => $r['eventName'],
                        'message' => $r['description'],
                        'createdDate' => Carbon::now(),
                    ]);
                }
                $model->save();
            }
        }
    }

    public function customDateReminderSchedule()
    {
        $currentDate = Carbon::now();
        $todate = $currentDate->startOfDay();
        $fromDate = $currentDate->endOfDay();

        $reminderQuery = Reminders::select(['reminders.*'])
            ->with(['reminderUsers'])
            ->where('reminders.frequency', '=', FrequencyEnum::OneTime->value)
            ->where('reminders.isRepeated', '=', 0)
            ->whereDate('reminders.startDate', '>=', $todate)
            ->whereDate('reminders.startDate', '<=', $fromDate);

        $reminders = $reminderQuery->get();

        if ($reminders != null && $reminders->count() > 0) {
            foreach ($reminders as $r) {
                foreach ($r['reminderUsers'] as $users) {
                    $duration = Carbon::create($currentDate->format("Y"), $currentDate->format("m"), $currentDate->format("d"),  $r['startDate']->format("h"), $r['startDate']->format("i"), $r['startDate']->format("s"));
                    $model = ReminderSchedulers::create([
                        'duration' => $duration,
                        'isActive' => 1,
                        'frequency' => $r['frequency'],
                        'userId' => $users['userId'],
                        'isRead' => 0,
                        'isEmailNotification' => $r['isEmailNotification'],
                        'subject' => $r['eventName'],
                        'message' => $r['description'],
                        'createdDate' => Carbon::now(),
                    ]);
                }
                $model->save();
            }
        }
    }

    public function reminderSchedule()
    {
        $schedulerStatus = $this->connectionMappingRepository->getSchedulerServiceStatus();
        if (!$schedulerStatus) {
            $this->connectionMappingRepository->setSchedulerServiceStatus(true);
            $this->reminderSchedulerServiceQuery();
            $this->connectionMappingRepository->setSchedulerServiceStatus(false);
        }
    }

    public function sendEmailSuppliersSchedule()
    {
        $schedulerStatus = $this->connectionMappingRepository->getEmailSchedulerStatus();
        if (!$schedulerStatus) {
            $this->connectionMappingRepository->setEmailSchedulerStatus(true);
            $this->sendEmailSchedulerCommand();
            $this->connectionMappingRepository->setEmailSchedulerStatus(false);
        }
    }

    public function reminderSchedulerServiceQuery()
    {
        $currentDate = Carbon::now();
        $todayStart = $currentDate->copy()->startOfDay();
        $todayEnd = $currentDate->copy()->endOfDay();

        $reminderSchedulers = ReminderSchedulers::select(['reminderSchedulers.*'])
            ->where('isActive', '=', 1)
            ->where('duration', '<=', $currentDate)
            ->orderBy('duration', 'ASC')
            ->take(10)
            ->get();

        if ($reminderSchedulers->count() > 0) {

            foreach ($reminderSchedulers as $reminderScheduler) {

                $reminderSchedulerId = $reminderScheduler->id;

                try {
                    DB::beginTransaction();

                    $existingNotification = UserNotifications::where('reminderSchedulerId', $reminderSchedulerId)
                        ->where('createdDate', '>=', $todayStart)
                        ->where('createdDate', '<=', $todayEnd)
                        ->lockForUpdate()
                        ->exists();

                    if (!$existingNotification) {
                        UserNotifications::create([
                            'userId' => $reminderScheduler['userId'],
                            'reminderSchedulerId' => $reminderSchedulerId,
                            'isRead' => 0,
                            'message' => $reminderScheduler['message'],
                        ]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error creating notification: ' . $e->getMessage());
                }

                $user = Users::where('id', $reminderScheduler['userId'])->first();

                if ($reminderScheduler->isEmailNotification && !empty($user->email)) {
                    $sendEmailObject = clone  $reminderScheduler;
                    $sendEmailObject['to_address'] = $user->email;

                    try {
                        $this->emailRepository->sendEmail($sendEmailObject);
                        $reminderScheduler->isActive = false;
                    } catch (\Exception $e) {
                        Log::error('Email send failed for reminder scheduler: ' . $e->getMessage());
                    }
                } else {
                    $reminderScheduler->isActive = false;
                }

                $reminderScheduler->save();
            }
        }
    }

    public function sendEmailSchedulerCommand()
    {
        $sendEmailSchduler = SendEmails::select(['sendEmails.*'])
            ->where('isSend', '=', 0)
            ->orderBy('createdDate', 'DESC')
            ->take(10)
            ->get();

        if ($sendEmailSchduler->count() > 0) {
            foreach ($sendEmailSchduler as $sendEmail) {
                if (!empty($sendEmail->email)) {
                    $sendEmailObject = clone $sendEmail;

                    try {
                        $sendEmailObject['to_address'] = $sendEmail->email;
                        $sendEmailObject['from_address'] = $sendEmail->fromEmail;
                        $this->emailRepository->sendEmail($sendEmailObject);
                    } catch (\Exception $e) {
                        echo 'exception: ' . $e->getMessage();
                    }
                }
                $sendEmail->isSend = true;
                $sendEmail->save();
            }
        }
    }
}
