<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });

        DB::statement('DELETE p1 FROM password_resets p1 INNER JOIN password_resets p2 ON p1.email = p2.email AND p1.id > p2.id');

        Schema::table('password_resets', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    public function down()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('password_resets', function (Blueprint $table) {
            $table->index('email');
        });
    }
};
