<?php

namespace App\Repositories\Implementation;

use App\Models\LoginAudit;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\LoginAuditRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class LoginAuditRepository.
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

        $orderByArray = explode(' ', $attributes->orderBy);
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

        $query = $this->applyFilters($query, $attributes);

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getLoginAuditsCount($attributes)
    {
        $query = LoginAudit::query();

        $query = $this->applyFilters($query, $attributes);

        $count = $query->count();
        return $count;
    }

    /**
     * Apply filters to the query based on provided attributes.
     * Extracts common filter logic used by both getLoginAudits and getLoginAuditsCount.
     *
     * @param Builder $query
     * @param mixed $attributes
     * @return Builder
     * @throws \InvalidArgumentException when loginTime format is invalid
     */
    private function applyFilters(Builder $query, $attributes): Builder
    {
        if (isset($attributes->userName) && $attributes->userName) {
            $query = $query->where('userName', 'like', '%' . $attributes->userName . '%');
        }

        if (isset($attributes->status) && $attributes->status) {
            $query = $query->where('status', '=', $attributes->status);
        }

        if (isset($attributes->loginTime) && $attributes->loginTime) {
            try {
                $parsed = Carbon::parse(str_replace('/', '-', $attributes->loginTime));
                $startDate = $parsed->copy()->startOfDay();
                $endDate = $parsed->copy()->endOfDay();
                
                $query = $query->where('loginTime', '>=', $startDate)
                    ->where('loginTime', '<=', $endDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid loginTime format. Please use a valid date format (e.g., YYYY-MM-DD or DD/MM/YYYY).');
            }
        }

        return $query;
    }
}