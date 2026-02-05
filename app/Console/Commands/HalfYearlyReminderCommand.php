<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class HalfYearlyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:halfYearly';

    /**
     * @var string
     */
    protected $description = 'Half Yearly Notification Handler.';

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
        $this->notificationRepository->halfYearlyReminder();
        $this->info('Half Yearly Reminder...');
    }
}
