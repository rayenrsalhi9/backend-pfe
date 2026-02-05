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
        Schema::create('loginAudits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userName')->nullable();
            $table->string('loginTime');
            $table->string('remoteIP')->nullable();
            $table->string('status')->nullable();
            $table->string('provider')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loginAudits');
    }
};
