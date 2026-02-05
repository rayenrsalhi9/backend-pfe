<?php

namespace App\Repositories\Contracts;

interface EmailRepositoryInterface
{
    public function sendEmail(array $attribute);
}
