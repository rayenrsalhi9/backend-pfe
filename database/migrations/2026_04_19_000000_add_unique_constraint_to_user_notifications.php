<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        try {
            DB::statement('ALTER TABLE userNotifications ADD CONSTRAINT unique_user_notification_check UNIQUE (userId, document_id_bucket, message, date_bucket)');
        } catch (\Exception $e) {
            // Constraint may already exist
        }
    }

    public function down()
    {
        try {
            DB::statement('ALTER TABLE userNotifications DROP CONSTRAINT unique_user_notification_check');
        } catch (\Exception $e) {
            // Constraint may not exist
        }
    }
};