<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ResponsesAuditRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Exception;

class ResponseAuditTrailController extends Controller
{
    protected $responseAuditTrailRepository;

    public function __construct(ResponsesAuditRepositoryInterface $responseAuditTrailRepository)
    {
        $this->responseAuditTrailRepository = $responseAuditTrailRepository;
    }

    /**
     * Get paginated response audit trails
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResponseAuditTrails(Request $request)
    {
        try {
            $queryString = (object) $request->all();

            // Set default values
            $queryString->pageSize = $queryString->pageSize ?? 50;
            $queryString->skip = $queryString->skip ?? 0;
            $queryString->searchQuery = $queryString->searchQuery ?? '';

            // Validate and set orderBy
            $allowedOrderColumns = ['createdDate', 'modifiedDate', 'operationName', 'responseType'];
            $allowedDirections = ['asc', 'desc'];
            $orderParts = explode(' ', $queryString->orderBy ?? 'createdDate desc');
            $column = in_array($orderParts[0], $allowedOrderColumns) ? $orderParts[0] : 'createdDate';
            $direction = in_array(strtolower($orderParts[1] ?? 'desc'), $allowedDirections) ? $orderParts[1] : 'desc';
            $queryString->orderBy = "$column $direction";

            $result = $this->responseAuditTrailRepository->getResponseAuditTrails($queryString);

            return response()->json($result['data'])->withHeaders([
                'totalCount' => $result['totalCount'],
                'pageSize' => $result['pageSize'],
                'skip' => $result['skip']
            ]);
        } catch (QueryException $e) {
            Log::error('Database error in response audit: ' . $e->getMessage());
            return response()->json([
                'error' => 'Database error occurred',
                'message' => 'Unable to retrieve audit data at this time'
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected error in response audit: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Get single response audit trail by ID
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResponseAuditTrail($id)
    {
        try {
            $auditTrail = $this->responseAuditTrailRepository->getResponseAuditTrailById($id);

            if (!$auditTrail) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Response audit trail not found'
                ], 404);
            }

            return response()->json($auditTrail);
        } catch (Exception $e) {
            Log::error('Error fetching response audit trail: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to fetch audit trail'
            ], 500);
        }
    }

    /**
     * Get response audit transactions for analytics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseTransactions(Request $request)
    {
        try {
            $year = $request->get('year');
            $month = $request->get('month');

            $transactions = $this->responseAuditTrailRepository->getResponseTransactions($year, $month);

            return response()->json($transactions);
        } catch (Exception $e) {
            Log::error('Error fetching response transactions: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to fetch transaction data'
            ], 500);
        }
    }

    /**
     * Get forums dropdown for filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForumsDropdown()
    {
        try {
            $forums = $this->responseAuditTrailRepository->getForumsDropdown();

            return response()->json($forums);
        } catch (Exception $e) {
            Log::error('Error fetching forums dropdown: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to fetch forums data'
            ], 500);
        }
    }

    /**
     * Get users dropdown for filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersDropdown()
    {
        try {
            $users = $this->responseAuditTrailRepository->getUsersDropdown();

            return response()->json($users);
        } catch (Exception $e) {
            Log::error('Error fetching users dropdown: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to fetch users data'
            ], 500);
        }
    }

    /**
     * Create new response audit trail
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createResponseAuditTrail(Request $request)
    {
        $validatedData = $request->validate([
            'forumId' => 'required|uuid|exists:forums,id',
            'responseId' => 'required|uuid',
            'responseType' => 'required|in:comment,reaction',
            'operationName' => 'required|in:Created,Updated,Deleted',
            'responseContent' => 'nullable|string',
            'previousContent' => 'nullable|string',
            'ipAddress' => 'nullable|ip',
            'userAgent' => 'nullable|string'
        ]);

        try {
            $auditTrail = $this->responseAuditTrailRepository->createResponseAuditTrail($validatedData);

            return response()->json($auditTrail, 201);
        } catch (Exception $e) {
            Log::error('Error creating response audit trail: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to create audit trail'
            ], 500);
        }
    }

    /**
     * Update response audit trail
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateResponseAuditTrail($id, Request $request)
    {
        $validatedData = $request->validate([
            'responseContent' => 'nullable|string',
            'previousContent' => 'nullable|string',
            'ipAddress' => 'nullable|ip',
            'userAgent' => 'nullable|string'
        ]);

        try {
            $auditTrail = $this->responseAuditTrailRepository->updateResponseAuditTrail($id, $validatedData);

            if (!$auditTrail) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Response audit trail not found'
                ], 404);
            }

            return response()->json($auditTrail);
        } catch (Exception $e) {
            Log::error('Error updating response audit trail: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to update audit trail'
            ], 500);
        }
    }

    /**
     * Delete response audit trail
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteResponseAuditTrail($id)
    {
        try {
            $deleted = $this->responseAuditTrailRepository->deleteResponseAuditTrail($id);

            if (!$deleted) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Response audit trail not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Response audit trail deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting response audit trail: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Unable to delete audit trail'
            ], 500);
        }
    }
}
