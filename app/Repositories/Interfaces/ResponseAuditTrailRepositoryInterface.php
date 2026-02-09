<?php

namespace App\Repositories\Interfaces;

interface ResponseAuditTrailRepositoryInterface
{
    /**
     * Get paginated response audit trails
     *
     * @param object $queryString
     * @return array
     */
    public function getResponseAuditTrails($queryString);

    /**
     * Get single response audit trail by ID
     *
     * @param string $id
     * @return object|null
     */
    public function getResponseAuditTrailById($id);

    /**
     * Get count of response audit trails
     *
     * @param object $queryString
     * @return int
     */
    public function getResponseAuditTrailsCount($queryString);

    /**
     * Create new response audit trail
     *
     * @param array $data
     * @return object
     */
    public function createResponseAuditTrail($data);

    /**
     * Update response audit trail
     *
     * @param string $id
     * @param array $data
     * @return object|null
     */
    public function updateResponseAuditTrail($id, $data);

    /**
     * Delete response audit trail
     *
     * @param string $id
     * @return bool
     */
    public function deleteResponseAuditTrail($id);

    /**
     * Get response audit transactions for analytics
     *
     * @param string|null $year
     * @param string|null $month
     * @return array
     */
    public function getResponseTransactions($year = null, $month = null);

    /**
     * Get forums dropdown for filtering
     *
     * @return array
     */
    public function getForumsDropdown();

    /**
     * Get users dropdown for filtering
     *
     * @return array
     */
    public function getUsersDropdown();
}