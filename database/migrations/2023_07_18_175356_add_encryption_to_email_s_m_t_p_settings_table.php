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
        Schema::table('emailSMTPSettings', function (Blueprint $table) {
            $table->string('encryption')->nullable()->after('isDefault');
            $table->string('fromName')->nullable()->after('isDefault');
            $table->dropColumn('isEnableSSL');
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
