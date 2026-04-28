<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('reminders', 'event_name')) {
            if (!Schema::hasColumn('reminders', 'eventName')) {
                Schema::table('reminders', function (Blueprint $table) {
                    $table->string('eventName')->nullable()->after('id');
                });
            }

            DB::update('UPDATE reminders SET eventName = COALESCE(event_name, \'\') WHERE isDeleted = 0');
            Schema::table('reminders', function (Blueprint $table) {
                $table->dropColumn('event_name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('reminders', 'eventName')) {
            if (!Schema::hasColumn('reminders', 'event_name')) {
                Schema::table('reminders', function (Blueprint $table) {
                    $table->string('event_name')->nullable()->after('id');
                });
            }

            DB::update('UPDATE reminders SET event_name = COALESCE(eventName, \'\') WHERE isDeleted = 0');
            Schema::table('reminders', function (Blueprint $table) {
                $table->dropColumn('eventName');
            });
        }
    }
};