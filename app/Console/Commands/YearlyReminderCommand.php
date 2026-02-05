<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class YearlyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:yearly';

    /**
     * @var string
     */
    protected $description = 'Yearly Notification Handler.';

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
        $this->notificationRepository->yearlyReminder();
        $this->info('Yearly Reminder...');
    }
}
