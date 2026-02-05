<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardController extends Controller
{
    private $dashboardRepository;

    public function __construct(DashboardRepositoryInterface $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    public function getDailyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getDailyReminders($month,$year);
        return response()->json($modal);
    }

    public function getWeeklyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getWeeklyReminders($month,$year);
        return response()->json($modal);
    }

    public function getMonthlyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getMonthlyReminders($month,$year);
        return response()->json($modal);
    }

    public function getQuarterlyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getQuarterlyReminders($month,$year);
        return response()->json($modal);
    }

    public function getHalfYearlyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getHalfYearlyReminders($month,$year);
        return response()->json($modal);
    }
    
    public function getYearlyReminders($month,$year)
    {
        $modal = $this->dashboardRepository->getYearlyReminders($month,$year);
        return response()->json($modal);
    }

    public function getOneTimeReminder($month,$year)
    {
        $modal = $this->dashboardRepository->getOneTimeReminder($month,$year);
        return response()->json($modal);
    }
}
