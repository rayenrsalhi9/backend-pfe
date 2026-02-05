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
        Schema::create('forums', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->enum('privacy',array('public','private'))->nullable()->default('public');
            $table->boolean('closed')->nullable()->default(false);
            $table->uuid('category_id');
            $table->uuid('sub_category_id')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('forum_categories')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('forum_sub_categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('forums');
    }
};
