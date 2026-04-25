<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('reminders', 'eventName')) {
            Schema::table('reminders', function (Blueprint $table) {
                $table->string('eventName')->nullable()->after('id');
            });

            if (Schema::hasColumn('reminders', 'event_name')) {
                DB::update('UPDATE reminders SET eventName = event_name');
                Schema::table('reminders', function (Blueprint $table) {
                    $table->dropColumn('event_name');
                });
            }
        }
    }

    public function down()
    {
        if (!Schema::hasColumn('reminders', 'event_name')) {
            Schema::table('reminders', function (Blueprint $table) {
                $table->string('event_name')->nullable()->after('id');
            });

            if (Schema::hasColumn('reminders', 'eventName')) {
                DB::update('UPDATE reminders SET event_name = eventName');
                Schema::table('reminders', function (Blueprint $table) {
                    $table->dropColumn('eventName');
                });
            }
        }
    }
};