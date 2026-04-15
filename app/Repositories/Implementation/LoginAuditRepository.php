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

        $orderBy = 'loginTime';
        $direction = 'asc';

        if (isset($attributes->orderBy) && is_string($attributes->orderBy) && $attributes->orderBy) {
            $orderByArray = explode(' ', $attributes->orderBy);
            $orderBy = $orderByArray[0];
            $direction = strtolower($orderByArray[1] ?? 'asc');
        }

        $allowedColumns = ['userName', 'loginTime', 'remoteIP', 'status'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'loginTime';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query = $query->orderBy($orderBy, $direction);

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

        if (isset($attributes->loginTime) && is_string($attributes->loginTime) && $attributes->loginTime) {
            try {
                $loginTimeValue = str_replace('/', '-', $attributes->loginTime);
                $parsed = null;

                if (Carbon::hasFormatWithModifiers($loginTimeValue, 'Y-m-d')) {
                    $parsed = Carbon::createFromFormat('Y-m-d', $loginTimeValue);
                } elseif (Carbon::hasFormatWithModifiers($loginTimeValue, 'd-m-Y')) {
                    $parsed = Carbon::createFromFormat('d-m-Y', $loginTimeValue);
                }

                if (!$parsed || !$parsed->isValid()) {
                    throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid loginTime format. Please use a valid date format (e.g., YYYY-MM-DD or DD/MM/YYYY).');
                }

                $startDate = $parsed->copy()->startOfDay();
                $endDate = $parsed->copy()->endOfDay();

                $query = $query->where('loginTime', '>=', $startDate)
                    ->where('loginTime', '<=', $endDate);
            } catch (\Throwable $e) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid loginTime format. Please use a valid date format (e.g., YYYY-MM-DD or DD/MM/YYYY).');
            }
        }

        return $query;
    }
}