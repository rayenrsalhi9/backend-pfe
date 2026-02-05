<?php

namespace App\Repositories\Implementation;


use App\Repositories\Contracts\ConnectionMappingRepositoryInterface;

//use Your Model

class ConnectionMappingRepository implements ConnectionMappingRepositoryInterface
{
    /**
     * @var Model
     */
    private bool $schedulerStatus = false;
    private bool $emailSchedulerStatus = false;

    /**
     *
     *
     * @param Model $model
     */
    public function getSchedulerServiceStatus()
    {
        return $this->schedulerStatus;
    }

    public function setSchedulerServiceStatus(bool $status)
    {
        return $this->schedulerStatus = $status;
    }

    public function getEmailSchedulerStatus()
    {
        return $this->emailSchedulerStatus;
    }

    public function setEmailSchedulerStatus(bool $status)
    {
        return $this->emailSchedulerStatus = $status;
    }

}
