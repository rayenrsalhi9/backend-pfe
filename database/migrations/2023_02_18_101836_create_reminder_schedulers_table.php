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
        Schema::create('reminderSchedulers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('duration');
            $table->boolean('isActive');
            $table->integer('frequency')->nullable();
            $table->dateTime('createdDate');
            $table->uuid('documentId')->nullable();
            $table->foreign('documentId')->references('id')->on('documents');
            $table->uuid('userId')->nullable(false);
            $table->foreign('userId')->references('id')->on('users');
            $table->boolean('isRead');
            $table->boolean('isEmailNotification');
            $table->string('subject')->nullable();
            $table->string('message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminderSchedulers');
    }
};
