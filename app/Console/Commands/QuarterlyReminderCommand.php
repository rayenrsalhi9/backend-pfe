<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class QuarterlyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:quarterly';

    /**
     * @var string
     */
    protected $description = 'Quarterly Notification Handler.';

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
        $this->notificationRepository->quarterlyReminder();
        $this->info('Quarterly Reminder...');
    }
}
