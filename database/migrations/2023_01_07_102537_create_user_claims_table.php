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
        Schema::create('userClaims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actionId')->nullable(false);
            $table->foreign('actionId')->references('id')->on('actions');
            $table->uuid('userId')->nullable(false);
            $table->foreign('userId')->references('id')->on('users');
            $table->string('claimType')->nullable();
            $table->string('claimValue')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userClaims');
    }
};
