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
        Schema::create('roleClaims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actionId')->nullable(false);
            $table->foreign('actionId')->references('id')->on('actions');
            $table->uuid('roleId')->nullable(false);
            $table->foreign('roleId')->references('id')->on('roles');
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
        Schema::dropIfExists('roleClaims');
    }
};
