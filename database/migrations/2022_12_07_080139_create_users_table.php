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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('isDeleted');
            $table->string('userName')->nullable();
            $table->string('normalizedUserName')->nullable();
            $table->string('email')->nullable();
            $table->string('normalizedEmail')->nullable();
            $table->boolean('emailConfirmed');
            $table->string('password')->nullable();
            $table->string('securityStamp')->nullable();
            $table->string('concurrencyStamp')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->boolean('phoneNumberConfirmed');
            $table->boolean('isConnected');
            $table->boolean('twoFactorEnabled');
            $table->timestamp('lockoutEnd')->nullable();
            $table->boolean('lockoutEnabled');
            $table->integer('accessFailedCount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
