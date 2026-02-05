<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class CustomDateReminderScheduleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:customDateReminderSchedule';

    /**
     * @var string
     */
    protected $description = 'Custom Date Reminder Notification Handler.';

    /**
     * @var NotificationScheduleInterface
     */
    private $notificationRepository;

    /**
     * @param NotificationScheduleInterface $link
     */
    public function __construct(NotificationScheduleInterface $notificationRepository)
    {
        parent::__construct();
        $this->notificationRepository = $notificationRepository;
    }

    public function handle()
    {
        $this->notificationRepository->customDateReminderSchedule();
        $this->info('Custom Date  Reminder...');
    }
}
