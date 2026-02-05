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
        Schema::create('sendEmails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject')->nullable();
            $table->string('message')->nullable();
            $table->string('fromEmail')->nullable();
            $table->uuid('documentId')->nullable();
            $table->foreign('documentId')->references('id')->on('documents');
            $table->boolean('isSend');
            $table->string('email')->nullable();
            $table->uuid('createdBy')->nullable(false);
            $table->foreign('createdBy')->references('id')->on('users');
            $table->string('modifiedBy');
            $table->string('deletedBy');
            $table->boolean('isDeleted');
            $table->dateTime('createdDate');
            $table->dateTime('modifiedDate');
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sendEmails');
    }
};
