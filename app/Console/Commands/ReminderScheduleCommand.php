<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class ReminderScheduleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:reminderSchedule';

    /**
     * @var string
     */
    protected $description = 'Reminder Schedule Notification Handler.';

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
        $this->notificationRepository->reminderSchedule();
        $this->info('Reminder Schedule...');
    }
}
