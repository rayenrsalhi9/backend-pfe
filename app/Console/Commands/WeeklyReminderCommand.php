<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class WeeklyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:weekly';

    /**
     * @var string
     */
    protected $description = 'Weekly Notification Handler.';

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
        $this->notificationRepository->weeklyReminder();
        $this->info('Weekly Reminder...');
    }
}
