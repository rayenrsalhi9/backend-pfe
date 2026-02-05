<?php

namespace App\Repositories\Contracts;



interface ConnectionMappingRepositoryInterface
{
    public function getSchedulerServiceStatus();
    public function setSchedulerServiceStatus(bool $status);
    public function getEmailSchedulerStatus();
    public function setEmailSchedulerStatus(bool $status);
}
