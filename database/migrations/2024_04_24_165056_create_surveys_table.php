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
        Schema::create('surveys', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->string('title');
            $table->enum('type',array('simple','rating','response','satisfaction'))->nullable()->default('simple');
            $table->enum('privacy',array('public','private'))->nullable()->default('public');
            $table->uuid('created_by')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('closed')->default(false);
            $table->boolean('blog')->default(false);
            $table->boolean('forum')->default(false);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surveys');
    }
};
