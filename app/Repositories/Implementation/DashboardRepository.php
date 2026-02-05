<?php

namespace App\Repositories\Implementation;

use App\Models\FrequencyEnum;
use App\Models\QuarterEnum;
use App\Models\Reminders;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardRepository  implements DashboardRepositoryInterface
{

    public function getDailyReminders($month, $year)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');

        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");

        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();

        $reminderQuery = Reminders::select(['reminders.*'])
            ->where('frequency', '=', FrequencyEnum::Daily->value)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            })
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->with(['dailyReminders']);

        $reminders = $reminderQuery->get();
        $result = array();
        foreach ($reminders as $r) {
            $reminderStartDate = $startDate <= $r->startDate ? $r->startDate : $startDate;
            $reminderEndDate = $r->endDate && $endDate >= $r->endDate ? $r->endDate : $endDate;

            $datediff = strtotime($reminderEndDate->endOfDay()) - strtotime($reminderStartDate->startOfDay());
            $dateDiffinDays = round($datediff / (60 * 60 * 24));

            $dateRang = range(0, $dateDiffinDays - 1);
            foreach ($dateRang as $rang) {
                $tempDate = date('Y-m-d', strtotime($reminderStartDate . '+ ' . $rang . ' days'));
                $dayofweek = date('w', strtotime($tempDate));

                $array = array_filter($r['dailyReminders']->toArray(), function ($item) use ($dayofweek) {
                    return $item["dayOfWeek"] == $dayofweek && $item["isActive"] == 1;
                });

                if (count($array) > 0) {
                    array_push($result, [
                        'Start' => $tempDate,
                        'End' => $tempDate,
                        'Title' => $r->subject,
                    ]);
                }
            }
        }

        return $result;
    }

    public function getWeeklyReminders($month, $year)
    {
        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");

        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();

        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.*'])
            ->where('frequency', '=', FrequencyEnum::Weekly->value)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            });

        $reminders = $reminderQuery->get();
        $result = array();
        foreach ($reminders as $r) {
            $reminderStartDate = $startDate <= $r->startDate ? $r->startDate : $startDate;
            $reminderEndDate = $r->endDate && $endDate >= $r->endDate ? $r->endDate : $endDate;

            $datediff = strtotime($reminderEndDate->endOfDay()) - strtotime($reminderStartDate->startOfDay());
            $dateDiffinDays = round($datediff / (60 * 60 * 24));

            $dateRang = range(0, $dateDiffinDays - 1);
            foreach ($dateRang as $rang) {
                $tempDate = date('Y-m-d', strtotime($reminderStartDate . '+ ' . $rang . ' days'));
                $dayofweek = date('w', strtotime($tempDate));

                if ($r["dayOfWeek"] == $dayofweek) {
                    array_push($result, [
                        'Start' => $tempDate,
                        'End' => $tempDate,
                        'Title' => $r->subject,
                    ]);
                }
            }
        }

        return $result;
    }

    public function getMonthlyReminders($month, $year)
    {
        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");
        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();

        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.*'])
            ->where('frequency', '=', FrequencyEnum::Monthly->value)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            });

        $reminders = $reminderQuery->get();
        $result = array();
        $lastDayOfMonth = date('d', strtotime($endDate));
        foreach ($reminders as $r) {
            $startDay = date('d', strtotime($r['startDate']));
            $lastDay = $startDay > $lastDayOfMonth ? $lastDayOfMonth : $startDay;
            array_push($result, [
                'Start' => date('Y-m-d', strtotime("$year-$month-$lastDay")),
                'End' => date('Y-m-d', strtotime("$year-$month-$lastDay")),
                'Title' => $r->subject,
            ]);
        }

        return $result;
    }

    public function getQuarterlyReminders($month, $year)
    {
        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");
        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();
        $currentQuater = $this->getCurretQuaterlyQuarter($startDate);

        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.id', 'reminders.subject', 'quarterlyReminders.*'])
            ->join('quarterlyReminders', 'reminders.id', '=', 'quarterlyReminders.reminderId')
            ->where('frequency', '=', FrequencyEnum::Quarterly->value)
            ->where('quarterlyReminders.quarter', '=', $currentQuater)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            })
            ->where(function ($query) use ($month) {
                $query->whereExists(function ($query) use ($month) {
                    $query->select(DB::raw(1))
                        ->from('quarterlyReminders')
                        ->whereRaw('quarterlyReminders.reminderId = reminders.id')
                        ->where('quarterlyReminders.month', '=', $month);
                });
            });

        $reminders = $reminderQuery->get();
        $result = array();
        foreach ($reminders as $r) {
            array_push($result, [
                'Start' => date('Y-m-d', strtotime("$year-$month-$r->day")),
                'End' => date('Y-m-d', strtotime("$year-$month-$r->day")),
                'Title' => $r->subject,
            ]);
        }

        return $result;
    }

    public function getHalfYearlyReminders($month, $year)
    {
        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");
        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();
        $currentQuater = $this->getCurretHalfYearlyQuarter($startDate);

        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.id', 'reminders.subject', 'halfYearlyReminders.*'])
            ->join('halfYearlyReminders', 'reminders.id', '=', 'halfYearlyReminders.reminderId')
            ->where('frequency', '=', FrequencyEnum::HalfYearly->value)
            ->where('halfYearlyReminders.quarter', '=', $currentQuater)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            })
            ->where(function ($query) use ($month) {
                $query->whereExists(function ($query) use ($month) {
                    $query->select(DB::raw(1))
                        ->from('halfYearlyReminders')
                        ->whereRaw('halfYearlyReminders.reminderId = reminders.id')
                        ->where('halfYearlyReminders.month', '=', $month);
                });
            });

        $reminders = $reminderQuery->get();
        $result = array();
        foreach ($reminders as $r) {
            array_push($result, [
                'Start' => date('Y-m-d', strtotime("$year-$month-$r->day")),
                'End' => date('Y-m-d', strtotime("$year-$month-$r->day")),
                'Title' => $r->subject,
            ]);
        }

        return $result;
    }

    public function getYearlyReminders($month, $year)
    {
        $monthStartDate = new DateTime();
        $monthStartDate->setDate($year, $month, 1);
        $monthEndDate = new DateTime();
        $monthEndDate->setDate($year, $month, 1);
        $monthEndDate->modify("+1 month")->modify("-1 days");
        $startDate = Carbon::createFromFormat('Y-m-d', $monthStartDate->format('Y-m-d'))->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $monthEndDate->format('Y-m-d'))->endOfDay();

        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.*'])
            ->where('frequency', '=', FrequencyEnum::Yearly->value)
            ->whereDate('reminders.startDate', '<=', $endDate)
            ->whereMonth('reminders.startDate', '=', $month)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            })
            ->where(function ($query) use ($startDate) {
                $query = $query->where('reminders.endDate', '')->orWhereNull('reminders.endDate')
                    ->orWhere(function ($query) use ($startDate) {
                        $query->whereDate('reminders.endDate', '>=', $startDate);
                    });
            });

        $reminders = $reminderQuery->get();
        $result = array();

        foreach ($reminders as $r) {
            $reminderStartDate = date('d', strtotime($r['startDate']));
            array_push($result, [
                'Start' => date('Y-m-d', strtotime("$year-$month-$reminderStartDate")),
                'End' => date('Y-m-d', strtotime("$year-$month-$reminderStartDate")),
                'Title' => $r->subject,
            ]);
        }

        return $result;
    }

    public function getOneTimeReminder($month, $year)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $reminderQuery = Reminders::select(['reminders.*'])
            ->where('frequency', '=', FrequencyEnum::OneTime->value)
            ->whereMonth('reminders.startDate', '=', $month)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('reminderUsers')
                    ->whereRaw('reminderUsers.reminderId = reminders.id')
                    ->where('reminderUsers.userId', '=', $userId);
            });

        $reminders = $reminderQuery->get();
        $result = array();

        foreach ($reminders as $r) {
            $reminderStartDate = date('d', strtotime($r['startDate']));
            array_push($result, [
                'Start' => date('Y-m-d', strtotime("$year-$month-$reminderStartDate")),
                'End' => date('Y-m-d', strtotime("$year-$month-$reminderStartDate")),
                'Title' => $r->subject,
            ]);
        }

        return $result;
    }

    public function getCurretHalfYearlyQuarter($date)
    {
        $year = date('Y', strtotime($date));
        if ($date > date('Y-m-d', strtotime("$year-1-1")) && $date <= date('Y-m-d', strtotime("$year-6-30"))) {
            return QuarterEnum::Quarter1->value;
        } else {
            return QuarterEnum::Quarter2->value;
        }
    }

    public function getCurretQuaterlyQuarter($date)
    {
        $year = date('Y', strtotime($date));
        if ($date > date('Y-m-d', strtotime("$year-1-1")) && $date <= date('Y-m-d', strtotime("$year-3-31"))) {
            return QuarterEnum::Quarter1->value;
        } else if ($date > date('Y-m-d', strtotime("$year-4-1")) && $date <= date('Y-m-d', strtotime("$year-6-30"))) {
            return QuarterEnum::Quarter2->value;
        } else if ($date > date('Y-m-d', strtotime("$year-7-1")) && $date <= date('Y-m-d', strtotime("$year-9-30"))) {
            return QuarterEnum::Quarter3->value;
        } else {
            return QuarterEnum::Quarter4->value;
        }
    }
}
