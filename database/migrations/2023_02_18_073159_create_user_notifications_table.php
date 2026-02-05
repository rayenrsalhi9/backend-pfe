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
        Schema::create('userNotifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId')->nullable(false);
            $table->foreign('userId')->references('id')->on('users');
            $table->string('type')->nullable()->default('reminder');
            $table->string('message')->nullable();
            $table->boolean('isRead');
            $table->uuid('documentId')->nullable();
            $table->foreign('documentId')->references('id')->on('documents');
            $table->dateTime('createdDate');
            $table->dateTime('modifiedDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userNotifications');
    }
};
