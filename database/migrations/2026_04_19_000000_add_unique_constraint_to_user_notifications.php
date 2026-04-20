<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE userNotifications ADD CONSTRAINT unique_user_notification_check UNIQUE (userId, documentId, message)');
    }

    public function down()
    {
        DB::statement('ALTER TABLE userNotifications DROP CONSTRAINT unique_user_notification_check');
    }
};