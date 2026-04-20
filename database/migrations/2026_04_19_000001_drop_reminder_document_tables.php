<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE reminders DROP FOREIGN KEY reminders_documentid_foreign');
        DB::statement('ALTER TABLE reminderSchedulers DROP FOREIGN KEY reminderschedulers_documentid_foreign');
        DB::statement('ALTER TABLE reminders DROP COLUMN documentId');
        DB::statement('ALTER TABLE reminderSchedulers DROP COLUMN documentId');
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->uuid('documentId')->nullable();
            $table->foreign('documentId')->references('id')->on('documents');
        });

        Schema::table('reminderSchedulers', function (Blueprint $table) {
            $table->uuid('documentId')->nullable();
            $table->foreign('documentId')->references('id')->on('documents');
        });
    }
};