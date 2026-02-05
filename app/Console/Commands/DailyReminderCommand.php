<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class DailyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:daily';

    /**
     * @var string
     */
    protected $description = 'Daily Notification Handler.';

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
        $this->notificationRepository->dailyReminder();
        $this->info('Daily Reminder...');
    }
}
