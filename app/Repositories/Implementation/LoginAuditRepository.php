<?php

namespace App\Repositories\Implementation;

use App\Models\LoginAudit;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\LoginAuditRepositoryInterface;
use Carbon\Carbon;

//use Your Model

/**
 * Class ScreenRepository.
 */
class LoginAuditRepository extends BaseRepository implements LoginAuditRepositoryInterface
{
      /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return LoginAudit::class;
    }
    public function getLoginAudits($attributes)
    {

        $query = LoginAudit::select();

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'userName') {
            $query = $query->orderBy('userName', $direction);
        } else if ($orderBy == 'loginTime') {
            $query = $query->orderBy('loginTime', $direction);
        } else if ($orderBy == 'remoteIP') {
            $query = $query->orderBy('remoteIP', $direction);
        } else if ($orderBy == 'status') {
            $query = $query->orderBy('status', $direction);
        }

        if (isset($attributes->userName) && $attributes->userName) {
            $query = $query->where('userName', 'like', '%' . $attributes->userName . '%');
        }

        if (isset($attributes->status) && $attributes->status) {
            $query = $query->where('status', '=', $attributes->status);
        }

        if (isset($attributes->loginTime) && $attributes->loginTime) {
            $date = date('Y-m-d', strtotime(str_replace('/', '-', $attributes->loginTime)));
            $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
            $query = $query->whereDate('loginTime', '>=', $startDate)
                ->whereDate('loginTime', '<=', $endDate);
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getLoginAuditsCount($attributes)
    {
        $query = LoginAudit::query();

        if (isset($attributes->userName) && $attributes->userName) {
            $query = $query->where('userName', 'like', '%' . $attributes->userName . '%');
        }

        if (isset($attributes->status) && $attributes->status) {
            $query = $query->where('status', '=', $attributes->status);
        }

        if (isset($attributes->loginTime) && $attributes->loginTime) {
            $date = date('Y-m-d', strtotime(str_replace('/', '-', $attributes->loginTime)));
            $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
            $query = $query->whereDate('loginTime', '>=', $startDate)
                ->whereDate('loginTime', '<=', $endDate);
        }

        $count = $query->count();
        return $count;
    }

}
