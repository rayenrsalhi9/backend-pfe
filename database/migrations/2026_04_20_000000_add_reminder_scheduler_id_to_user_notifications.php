<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('userNotifications', function (Blueprint $table) {
            $table->string('reminderSchedulerId')->nullable()->after('userId');
        });
    }

    public function down()
    {
        Schema::table('userNotifications', function (Blueprint $table) {
            $table->dropColumn('reminderSchedulerId');
        });
    }
};