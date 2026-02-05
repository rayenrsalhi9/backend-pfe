<?php

namespace App\Repositories\Contracts;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface EmailSMTPSettingRepositoryInterface extends BaseRepositoryInterface
{
   public function createEmailSMTP($attribute);
   public function updateEmailSMTP($attribute,$id);
}
