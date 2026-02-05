<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class MonthyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:monthy';

    /**
     * @var string
     */
    protected $description = 'Monthy Notification Handler.';

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
        $this->notificationRepository->monthyReminder();
        $this->info('Monthy Reminder...');
    }
}
