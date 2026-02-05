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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('categoryId')->nullable(false);
            $table->foreign('categoryId')->references('id')->on('categories');
            $table->string('name')->nullable();
            $table->string('extension')->nullable();
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->dateTime('createdDate');
            $table->uuid('createdBy')->nullable(false);
            $table->foreign('createdBy')->references('id')->on('users');
            $table->dateTime('modifiedDate');
            $table->string('modifiedBy');
            $table->boolean('isDeleted');
            $table->string('deletedBy')->nullable();
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
        Schema::dropIfExists('documents');
    }
};
