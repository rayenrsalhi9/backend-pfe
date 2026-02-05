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
        Schema::create('emailSMTPSettings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('host');
            $table->string('userName');
            $table->string('password');
            $table->boolean('isEnableSSL');
            $table->integer('port');
            $table->boolean('isDefault');
            $table->string('createdBy');
            $table->string('modifiedBy');
            $table->string('deletedBy')->nullable();
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
        Schema::dropIfExists('emailSMTPSettings');
    }
};
