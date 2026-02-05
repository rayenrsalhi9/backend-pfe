<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    public function getDailyReminders($month,$year);
    public function getWeeklyReminders($month,$year);
    public function getMonthlyReminders($month,$year);
    public function getQuarterlyReminders($month,$year);
    public function getHalfYearlyReminders($month,$year);
    public function getYearlyReminders($month,$year);
    public function getOneTimeReminder($month,$year);

}
