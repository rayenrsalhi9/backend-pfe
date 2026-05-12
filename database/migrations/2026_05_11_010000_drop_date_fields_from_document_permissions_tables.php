<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('documentRolePermissions', function (Blueprint $table) {
            $table->dropColumn(['startDate', 'endDate', 'isTimeBound']);
        });

        Schema::table('documentUserPermissions', function (Blueprint $table) {
            $table->dropColumn(['startDate', 'endDate', 'isTimeBound']);
        });
    }

    public function down()
    {
        Schema::table('documentRolePermissions', function (Blueprint $table) {
            $table->dateTime('startDate')->nullable();
            $table->dateTime('endDate')->nullable();
            $table->boolean('isTimeBound')->default(false);
        });

        Schema::table('documentUserPermissions', function (Blueprint $table) {
            $table->dateTime('startDate')->nullable();
            $table->dateTime('endDate')->nullable();
            $table->boolean('isTimeBound')->default(false);
        });
    }
};
