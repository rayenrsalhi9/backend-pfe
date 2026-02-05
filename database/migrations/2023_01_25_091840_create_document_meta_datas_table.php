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
        Schema::create('documentMetaDatas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('documentId')->nullable(false);
            $table->foreign('documentId')->references('id')->on('documents');
            $table->string('metatag')->nullable();
            $table->string('createdBy');
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
        Schema::dropIfExists('documentMetaDatas');
    }
};
