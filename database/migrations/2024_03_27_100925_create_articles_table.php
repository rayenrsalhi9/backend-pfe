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
        Schema::create('articles', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('short_text');
            $table->text('long_text');
            $table->text('picture');
            $table->enum('privacy',array('public','private'))->nullable()->default('public');
            $table->uuid('created_by');
            $table->uuid('article_category_id');
            $table->timestamps();

            $table->foreign('article_category_id')->references('id')->on('article_categories')->onDelete('cascade');
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
        Schema::dropIfExists('articles');
    }
};
