<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversation_users', function (Blueprint $table) {
            $table->unique(['conversation_id', 'user_id'], 'conversation_users_unique');
        });
    }

    public function down()
    {
        Schema::table('conversation_users', function (Blueprint $table) {
            $table->dropUnique('conversation_users_unique');
        });
    }
};