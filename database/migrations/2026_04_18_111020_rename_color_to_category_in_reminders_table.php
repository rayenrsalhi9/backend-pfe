<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE reminders ADD COLUMN category VARCHAR(255) NULL AFTER documentId');
        DB::statement('UPDATE reminders SET category = color WHERE color IS NOT NULL');
        DB::statement('ALTER TABLE reminders DROP COLUMN color');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE reminders ADD COLUMN color VARCHAR(255) NULL AFTER documentId');
        DB::statement('UPDATE reminders SET color = category WHERE category IS NOT NULL');
        DB::statement('ALTER TABLE reminders DROP COLUMN category');
    }
};
