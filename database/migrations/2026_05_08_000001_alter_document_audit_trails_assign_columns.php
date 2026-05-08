<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'documentAuditTrails'
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND COLUMN_NAME IN ('assignToUserId', 'assignToRoleId')
        ");

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `documentAuditTrails` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $columns = DB::select("
            SELECT COLUMN_NAME, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'documentAuditTrails'
              AND COLUMN_NAME IN ('assignToUserId', 'assignToRoleId')
        ");

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE `documentAuditTrails` MODIFY COLUMN `{$col->COLUMN_NAME}` TEXT NULL");
        }
    }

    public function down()
    {
        $columns = DB::select("
            SELECT COLUMN_NAME, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'documentAuditTrails'
              AND COLUMN_NAME IN ('assignToUserId', 'assignToRoleId')
        ");

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE `documentAuditTrails` MODIFY COLUMN `{$col->COLUMN_NAME}` CHAR(36) NULL");
        }

        Schema::table('documentAuditTrails', function ($table) {
            $table->foreign('assignToUserId')->references('id')->on('users');
            $table->foreign('assignToRoleId')->references('id')->on('roles');
        });
    }
};
