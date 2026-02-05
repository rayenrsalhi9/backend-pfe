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
        Schema::create('reminderUsers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reminderId')->nullable(false);
            $table->foreign('reminderId')->references('id')->on('reminders');
            $table->uuid('userId')->nullable(false);
            $table->foreign('userId')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminderUsers');
    }
};
