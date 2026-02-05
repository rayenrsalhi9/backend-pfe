<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;

class SendEmailSuppliersScheduleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:sendEmailSuppliers';

    /**
     * @var string
     */
    protected $description = 'Send Email Suppliers Notification Handler.';

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
        $this->notificationRepository->sendEmailSuppliersSchedule();
        $this->info('Send Email Suppliers Reminder...');
    }
}
