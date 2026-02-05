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
        Schema::table('users', function (Blueprint $table) {
            $table->string('matricule')->nullable()->after('id'); // Ajoute la colonne matricule
            $table->string('direction')->nullable()->after('matricule'); // Ajoute la colonne direction
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'matricule')) {
                $table->dropColumn('matricule');
            }
            if (Schema::hasColumn('users', 'direction')) {
                $table->dropColumn('direction');
            }
        });
    }
};
