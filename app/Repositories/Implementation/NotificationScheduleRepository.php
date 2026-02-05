<?php

namespace App\Repositories\Implementation;

use App\Models\Documents;
use App\Models\ReminderSchedulers;
use App\Repositories\Contracts\NotificationScheduleInterface;
use App\Repositories\Implementation\ConnectionMappingRepository;
use Carbon\Carbon;
use App\Models\FrequencyEnum;
use App\Models\Reminders;
use App\Models\SendEmails;
use App\Models\UserNotifications;
use App\Models\Users;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\EmailRepositoryInterface;
use Illuminate\Support\Facades\Storage;


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
                    'subject' => $r['subject'],
                    'message' => $r['message'],
                    'createdDate' => Carbon::now(),
                    'documentId' => $r['documentId'],
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
                    'subject' => $r['subject'],
                    'message' => $r['message'],
                    'createdDate' => Carbon::now(),
                    'documentId' => $r['documentId'],
                ]);
            }
            $model->save();
        }
    }

    public function monthyReminder()
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
                        'subject' => $r['subject'],
                        'message' => $r['message'],
                        'createdDate' => Carbon::now(),
                        'documentId' => $r['documentId'],
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
                    'subject' => $r['subject'],
                    'message' => $r['message'],
                    'createdDate' => Carbon::now(),
                    'documentId' => $r['documentId'],
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
                    'subject' => $r['subject'],
                    'message' => $r['message'],
                    'createdDate' => Carbon::now(),
                    'documentId' => $r['documentId'],
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
                        'subject' => $r['subject'],
                        'message' => $r['message'],
                        'createdDate' => Carbon::now(),
                        'documentId' => $r['documentId'],
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
                        'subject' => $r['subject'],
                        'message' => $r['message'],
                        'createdDate' => Carbon::now(),
                        'documentId' => $r['documentId'],
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

        $reminderSchedulers = ReminderSchedulers::select(['reminderSchedulers.*'])
            ->where('isActive', '=', 1)
            ->where('duration', '<=', $currentDate)
            ->orderBy('duration', 'DESC')
            ->take(10)
            ->get();

        if ($reminderSchedulers->count() > 0) {

            foreach ($reminderSchedulers as $reminderScheduler) {

                $model = UserNotifications::create([
                    'userId' => $reminderScheduler['userId'],
                    'isRead' => 0,
                    'message' => $reminderScheduler['message'],
                    'documentId' => $reminderScheduler['documentId']
                ]);

                // $model->createdDate = Carbon::now();
                // $model->modifiedDate = Carbon::now();

                $model->save();

                $user = Users::where('id', $reminderScheduler['userId'])->first();

                if ($reminderScheduler->isEmailNotification) {

                    $sendEmailObject = clone  $reminderScheduler;

                    if ($reminderScheduler['documentId'] != null) {
                        $document =  $this->getDocument($reminderScheduler['documentId']);
                        $fileupload = $document->url;
                        if (Storage::disk('local')->exists($fileupload)) {
                            $ext = pathinfo($document->url, PATHINFO_EXTENSION);
                            $sendEmailObject['path'] = Storage::path($fileupload);
                            $sendEmailObject['mime_type'] = Storage::mimeType($fileupload);
                            $sendEmailObject['file_name'] = $document->name . '.' . $ext;
                        }
                    }
                    $sendEmailObject['to_address'] = $user->email;

                    try {
                        $this->emailRepository->sendEmail($sendEmailObject);
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                }

                $reminderScheduler->isActive = false;
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
                if (!empty($sendEmail->email)  && $sendEmail->documentId != null) {
                    $document =  $this->getDocument($sendEmail->documentId);

                    $fileupload = $document->url;

                    $sendEmailObject = clone  $sendEmail;

                    if (Storage::disk('local')->exists($fileupload)) {
                        $ext = pathinfo($document->url, PATHINFO_EXTENSION);
                        $sendEmailObject['path'] = Storage::path($fileupload);
                        $sendEmailObject['mime_type'] = Storage::mimeType($fileupload);
                        $sendEmailObject['file_name'] = $document->name . '.' . $ext;
                    }

                    try {
                        $sendEmailObject['to_address'] = $sendEmail->email;
                        $sendEmailObject['from_address'] = $sendEmail->fromEmail;
                        $this->emailRepository->sendEmail($sendEmailObject);
                    } catch (\Exception $e) {
                        echo 'exception: ' . $e->getMessage();
                    }
                }
                // }
                $sendEmail->isSend = true;
                $sendEmail->save();
            }
        }
    }

    private function getDocument($documentId)
    {
        $doc = Documents::where('id', $documentId)->first();
        return $doc;
    }
}
