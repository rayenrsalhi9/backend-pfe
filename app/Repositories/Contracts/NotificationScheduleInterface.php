<?php

namespace App\Repositories\Contracts;



interface NotificationScheduleInterface
{
    public function dailyReminder();
    public function monthyReminder();
    public function weeklyReminder();
    public function quarterlyReminder();
    public function halfYearlyReminder();
    public function yearlyReminder();
    public function customDateReminderSchedule();
    public function reminderSchedule();
    public function sendEmailSuppliersSchedule();

}
