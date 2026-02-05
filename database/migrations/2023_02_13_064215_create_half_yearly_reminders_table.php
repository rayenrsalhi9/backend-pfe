<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('halfYearlyReminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reminderId')->nullable(false);
            $table->foreign('reminderId')->references('id')->on('reminders');
            $table->integer('day');
            $table->integer('month');
            $table->integer('quarter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('halfYearlyReminders');
    }
};
