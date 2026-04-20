<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class MonthlyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:monthly';

    /**
     * @var string
     */
    protected $description = 'Monthly Notification Handler.';

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
        $this->notificationRepository->monthlyReminder();
        $this->info('Monthly Reminder...');
    }
}
