<?php

namespace App\Repositories;

use App\Models\ResponseAuditTrails;
use App\Models\Forums;
use App\Models\Users;
use App\Repositories\Interfaces\ResponseAuditTrailRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResponseAuditTrailRepository implements ResponseAuditTrailRepositoryInterface
{
    /**
     * Apply filters to the query based on query string parameters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param object $queryString
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, $queryString)
    {
        // Apply filters
        if (!empty($queryString->forumId)) {
            $query->where('forumId', $queryString->forumId);
        }

        if (!empty($queryString->responseId)) {
            $query->where('responseId', $queryString->responseId);
        }

        if (!empty($queryString->operation)) {
            $query->where('operationName', $queryString->operation);
        }

        if (!empty($queryString->userId)) {
            $query->where('createdBy', $queryString->userId);
        }

        if (!empty($queryString->responseType) && $queryString->responseType !== 'all') {
            $query->where('responseType', $queryString->responseType);
        }

        if (!empty($queryString->dateFrom)) {
            $query->where('createdDate', '>=', $queryString->dateFrom);
        }

        if (!empty($queryString->dateTo)) {
            $query->where('createdDate', '<=', $queryString->dateTo);
        }

        // Search functionality
        if (!empty($queryString->searchQuery)) {
            $searchTerm = '%' . $queryString->searchQuery . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('responseContent', 'like', $searchTerm)
                    ->orWhere('previousContent', 'like', $searchTerm)
                    ->orWhereHas('forum', function ($forumQ) use ($searchTerm) {
                        $forumQ->where('title', 'like', $searchTerm);
                    })
                    ->orWhereHas('user', function ($userQ) use ($searchTerm) {
                        $userQ->where('name', 'like', $searchTerm);
                    })
                    ->orWhere('ipAddress', 'like', $searchTerm);
            });
        }

        return $query;
    }
    /**
     * Get paginated response audit trails
     *
     * @param object $queryString
     * @return array
     */
    public function getResponseAuditTrails($queryString)
    {
        $query = ResponseAuditTrails::with(['forum', 'user']);

        // Apply filters using the helper method
        $query = $this->applyFilters($query, $queryString);

        // Apply ordering with whitelist validation
        $allowedOrderColumns = ['createdDate', 'modifiedDate', 'operationName', 'responseType'];
        $allowedDirections = ['asc', 'desc'];
        $orderBy = $queryString->orderBy ?? 'createdDate desc';
        $orderParts = explode(' ', $orderBy);
        $column = in_array($orderParts[0], $allowedOrderColumns) ? $orderParts[0] : 'createdDate';
        $direction = in_array(strtolower($orderParts[1] ?? 'asc'), $allowedDirections) ? strtolower($orderParts[1] ?? 'asc') : 'desc';

        $query->orderBy($column, $direction);

        // Apply pagination
        $offset = $queryString->skip ?? 0;
        $limit = $queryString->pageSize ?? 50;

        $totalCount = $query->count();
        $results = $query->offset($offset)->limit($limit)->get();

        return [
            'data' => $results,
            'totalCount' => $totalCount,
            'pageSize' => $limit,
            'skip' => $offset
        ];
    }

    /**
     * Get single response audit trail by ID
     *
     * @param string $id
     * @return object|null
     */
    public function getResponseAuditTrailById($id)
    {
        return ResponseAuditTrails::with(['forum', 'user'])
            ->where('id', $id)
            ->first();
    }

    /**
     * Get count of response audit trails
     *
     * @param object $queryString
     * @return int
     */
    public function getResponseAuditTrailsCount($queryString)
    {
        $query = ResponseAuditTrails::query();

        // Apply filters using the helper method
        $query = $this->applyFilters($query, $queryString);

        return $query->count();
    }

    /**
     * Create new response audit trail
     *
     * @param array $data
     * @return object
     */
    public function createResponseAuditTrail($data)
    {
        return ResponseAuditTrails::create($data);
    }

    /**
     * Update response audit trail
     *
     * @param string $id
     * @param array $data
     * @return object|null
     */
    public function updateResponseAuditTrail($id, $data)
    {
        $auditTrail = ResponseAuditTrails::find($id);

        if ($auditTrail) {
            $auditTrail->update($data);
            return $auditTrail;
        }

        return null;
    }

    /**
     * Delete response audit trail
     *
     * @param string $id
     * @return bool
     */
    public function deleteResponseAuditTrail($id)
    {
        $auditTrail = ResponseAuditTrails::find($id);

        if ($auditTrail) {
            $auditTrail->isDeleted = true;
            $auditTrail->save();
            return true;
        }

        return false;
    }

    /**
     * Get response audit transactions for analytics
     *
     * @param string|null $year
     * @param string|null $month
     * @return array
     */
    public function getResponseTransactions($year = null, $month = null)
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;

        $query = ResponseAuditTrails::whereYear('createdDate', $year)
            ->whereMonth('createdDate', $month);

        $operations = $query->selectRaw('operationName, COUNT(*) as count')
            ->groupBy('operationName')
            ->pluck('count', 'operationName');

        $dailyData = ResponseAuditTrails::whereYear('createdDate', $year)
            ->whereMonth('createdDate', $month)
            ->selectRaw('DATE(createdDate) as date, operationName, COUNT(*) as count')
            ->groupBy('date', 'operationName')
            ->orderBy('date')
            ->get();

        return [
            'operations' => $operations,
            'daily' => $dailyData,
            'total' => $operations->sum()
        ];
    }

    /**
     * Get forums dropdown for filtering
     *
     * @return array
     */
    public function getForumsDropdown()
    {
        return Forums::select('id', 'title')
            ->where('closed', false)
            ->orderBy('title')
            ->get();
    }

    /**
     * Get users dropdown for filtering
     *
     * @return array
     */
    public function getUsersDropdown()
    {
        return Users::select('id', 'firstName', 'lastName')
            ->orderBy('firstName')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->firstName . ' ' . $user->lastName
                ];
            });
    }
}
